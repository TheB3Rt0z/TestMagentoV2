<?php

namespace MediaDivision\WishlistExt\Helper;

class ParticipantRepository
{
    private $participantFactory;
    private $connection;
    private $participantTable;
    private $wishlistItemTable;
    private $wishlistTable;
    private $storeManager;
    private $transportBuilder;
    private $inlineTranslation;
    private $wishlist;
    private $scopeConfig;
    private $customerRepositoryInterface;
    protected $_productloader;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \MediaDivision\WishlistExt\Model\ParticipantFactory $participantFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Wishlist\Model\Wishlist $wishlist,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ProductFactory $_productloader
    ) {
        $this->participantFactory = $participantFactory;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
        $this->wishlist = $wishlist;
        $this->scopeConfig = $scopeConfig;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->_productloader = $_productloader;

        $this->connection = $resourceConnection->getConnection();

        $this->participantTable = $this->connection->getTableName('md_wishlistext_participant');
        $this->wishlistItemTable = $this->connection->getTableName('wishlist_item');
        $this->wishlistTable = $this->connection->getTableName('wishlist');
    }

    public function removeParticipant($participantId, $customerId)
    {
        $wishlistCustomerId = $this->connection->fetchOne(
            "
            select w.customer_id from {$this->participantTable} p
            left join {$this->wishlistItemTable} wi on p.wishlist_item_id = wi.wishlist_item_id
            left join {$this->wishlistTable} w on wi.wishlist_id = w.wishlist_id
            where p.id = ?
        ",
            [$participantId]
        );

        if ($customerId != $wishlistCustomerId) {
            return false;
        }

        $this->connection->query("delete from {$this->participantTable} where id = ?", [$participantId]);

        return true;
    }

    public function getParticipants($wishlistItemId, $customerId, $code)
    {
        if (!$customerId && !$code) {
            return false;
        }
        $participants = $this->connection->fetchAll(
            "
            select p.*, wi.qty as wiqty, wi.owner_qty as wiownerqty from {$this->wishlistItemTable} wi
            left join {$this->wishlistTable} w on wi.wishlist_id = w.wishlist_id
            join {$this->participantTable} p on p.wishlist_item_id = wi.wishlist_item_id
            where wi.wishlist_item_id = ? and (w.customer_id = ? or w.sharing_code = ?)
        ",
            [$wishlistItemId, $customerId, $code],
            \PDO::FETCH_ASSOC
        );

        $participants = array_map(
            function ($el) {
                return new \Magento\Framework\DataObject($el);
            },
            $participants
        );

        return $participants;
    }

    public function __addParticipant($customerId, $data)
    {
        $wishlistItemId = @$data['wishlist_item_id'];
        $code = @$data['code'];

        $accessible = false;

        if ($code) {
            $accessible = $this->connection->fetchRow(
                "
            select wi.qty, wi.owner_qty, w.customer_id as owner_id from {$this->wishlistItemTable} wi
            left join {$this->wishlistTable} w on wi.wishlist_id = w.wishlist_id
            where wi.wishlist_item_id = ? and (w.customer_id = ? or w.sharing_code = ?)
        ",
                [$wishlistItemId, $customerId, $code],
                \PDO::FETCH_ASSOC
            );
        } elseif ($customerId) {
            $accessible = $this->connection->fetchOne(
                "
            select 1 from {$this->wishlistItemTable} wi
            left join {$this->wishlistTable} w on wi.wishlist_id = w.wishlist_id
            where wi.wishlist_item_id = ? and w.customer_id = ?
        ",
                [$wishlistItemId, $customerId],
                \PDO::FETCH_ASSOC
            );
        }

        if (!$accessible) {
            return false;
        }

        $participants = $this->connection->fetchAll(
            "
            select p.*, wi.product_id as productId, wi.qty as wiqty, wi.owner_qty as wiownerqty from {$this->wishlistItemTable} wi
            left join {$this->wishlistTable} w on wi.wishlist_id = w.wishlist_id
            join {$this->participantTable} p on p.wishlist_item_id = wi.wishlist_item_id
            where wi.wishlist_item_id = ? and (w.customer_id = ? or w.sharing_code = ?)
        ",
            [$wishlistItemId, $customerId, $code],
            \PDO::FETCH_ASSOC
        );

        $availableQty = $accessible['owner_qty'];
        $producId = '';
        foreach ($participants as $participant) {
            $availableQty -= $participant['qty'];
            $producId = $participant['productId'];
        }
        $product = $this->_productloader->create()->load($producId);
        $unit = $product->getAttributeText('base_price_unit');
        if (($availableQty - $data['qty']) < 0) {
            //throw new \Exception(__(sprintf("You can't add participant. Available qty %d", $availableQty)));
            throw new \Exception(__("Quantity available for sharing: %1", number_format($availableQty, 1, ',', '.').' '.$unit));
            //throw new \Exception(__("That didn't work.<br />There is nothing left of this product to share."));
        }

        $this->participantFactory->create()->addData($data)->save();

        // send email
        $customer = $this->customerRepositoryInterface->getById($accessible['owner_id']);

        $to = [
            'email' => $customer->getEmail(),
            'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
        ];

        $from = [
            'email' => $this->scopeConfig->getValue('trans_email/ident_support/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'name' => $this->scopeConfig->getValue('trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
        ];
        $this->sendEmail($from, $to, $data);

        return true;
    }

    public function addParticipant($customerId, $data)
    {
        $wishlistItemId = @$data['wishlist_item_id'];
        $code = @$data['code'];

        if (!$customerId && !$code) {
            return false;
        }

        $accessible = $this->connection->fetchRow(
            "
            select wi.qty, wi.product_id as productId, wi.owner_qty, w.customer_id as owner_id from {$this->wishlistItemTable} wi
            left join {$this->wishlistTable} w on wi.wishlist_id = w.wishlist_id
            where wi.wishlist_item_id = ? and (w.customer_id = ? or w.sharing_code = ?)
        ",
            [$wishlistItemId, $customerId, $code],
            \PDO::FETCH_ASSOC
        );

        if (!$accessible) {
            return false;
        }

        $participants = $this->connection->fetchAll(
            "
            select p.*, wi.product_id as productId, wi.qty as wiqty, wi.owner_qty as wiownerqty from {$this->wishlistItemTable} wi
            left join {$this->wishlistTable} w on wi.wishlist_id = w.wishlist_id
            join {$this->participantTable} p on p.wishlist_item_id = wi.wishlist_item_id
            where wi.wishlist_item_id = ? and (w.customer_id = ? or w.sharing_code = ?)
        ",
            [$wishlistItemId, $customerId, $code],
            \PDO::FETCH_ASSOC
        );

        $availableQty = $accessible['owner_qty'];
        $productId = '';

        if(empty($participants)){
            $productId = $this->connection->fetchOne(
                "
            select wi.product_id as productId from {$this->wishlistItemTable} wi
            where wi.wishlist_item_id = ?
            ",
                [$wishlistItemId],
                \PDO::FETCH_ASSOC
            );
        }

        foreach ($participants as $participant) {
            $availableQty -= $participant['qty'];
            $productId = $participant['productId'];
        }
        $product = $this->_productloader->create()->load($productId);
        $unit = $product->getAttributeText('base_price_unit');
        if (($availableQty - $data['qty']) < 0) {
            //throw new \Exception(__(sprintf("You can't add participant. Available qty %d", $availableQty)));
            throw new \Exception(__("Quantity available for sharing: %1", number_format($availableQty, 1, ',', '.').' '. $unit));
            //throw new \Exception(__("That didn't work.<br />There is nothing left of this product to share."));
        }

        $this->participantFactory->create()->addData($data)->save();

        $customer = $this->customerRepositoryInterface->getById($accessible['owner_id']);

        $to = [
            'email' => $customer->getEmail(),
            'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
        ];

        $from = [
            'email' => $this->scopeConfig->getValue('trans_email/ident_support/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'name' => $this->scopeConfig->getValue('trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
        ];
        $this->sendEmail($from, $to, $data);

        return true;
    }

    public function sendEmail($from, $to, $participant)
    {
        $this->inlineTranslation->suspend();

        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('wishlist_participant')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId()
                    ]
                )
                ->setTemplateVars(['participant' => $participant])
                ->setFrom($from)
                ->addTo($to['email'], $to['name'])
                ->getTransport();

            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
    /*public function addParticipant($wishlistItemId, $customerId, $code, $name, $qty)
    {
        if (!$customerId && !$code) {
            return false;
        }
        $accessible = $this->connection->fetchOne("
            select 1 from {$this->wishlistItemTable} wi
            left join {$this->wishlistTable} w on wi.wishlist_id = w.wishlist_id
            where wi.wishlist_item_id = ? and (w.customer_id = ? or w.sharing_code = ?)
        ", [$wishlistItemId, $customerId, $code], \PDO::FETCH_ASSOC);

        if (!$accessible) {
            return false;
        }

        $this->connection->query(
            "insert into {$this->participantTable} (wishlist_item_id, name, qty) values (?,?,?)",
            [$wishlistItemId, $name, $qty]);
        return true;
    }*/
}

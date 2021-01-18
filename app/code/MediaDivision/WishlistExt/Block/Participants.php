<?php

namespace MediaDivision\WishlistExt\Block;

use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\View\ConfigInterface;
use MediaDivision\WishlistExt\Model\ParticipantFactory;

class Participants extends \Magento\Wishlist\Block\Customer\Wishlist\Item\Column
{

    protected $participantFactory;
    protected $request;
    protected $codeModel = null;
    protected $customerSession;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Session $customerSession,
        ParticipantFactory $participantFactory,
        array $data = [],
        ConfigInterface $config = null,
        UrlBuilder $urlBuilder = null
    ) {
        parent::__construct($context, $httpContext, $data, $config, $urlBuilder);
        $this->participantFactory = $participantFactory;
        $this->customerSession = $customerSession;
        $this->request = $context->getRequest();
    }

    public function getParticipants()
    {
        $part = $this->participantFactory->create();
        $coll = $part->getCollection();
        $wishlistitemid = $this->getItem()->getId();
        return $coll->getItemsByColumnValue('wishlist_item_id', $wishlistitemid);
    }

    public function getEmailCode()
    {
        return $this->request->getParam('email_code');
    }

    public function getCodeModel()
    {
        if ($this->codeModel === null) {
            $this->codeModel = \Magento\Framework\App\ObjectManager::getInstance()
                ->create(\MediaDivision\WishlistExt\Model\Code::class)->load($this->getEmailCode(), 'code');
        }
        return $this->codeModel;
    }
    
    public function isWhatsapp()
    {
        return $this->request->getParam('whatsapp');
    }

    public function canAddParticipants()
    {
        $codeModel = $this->getCodeModel();
        $codeId = $codeModel->getId();

        if ($codeId) {
            $wishlistitemid = $this->getItem()->getId();
            $collection = \Magento\Framework\App\ObjectManager::getInstance()
                ->create(\MediaDivision\WishlistExt\Model\ResourceModel\ParticipantCollection::class)
                ->addFieldToFilter('code_id', $codeId)
                ->addFieldToFilter('wishlist_item_id', $wishlistitemid);
            if (count($collection)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    public function getCurrentCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    //    public function getCode()
    //    {
    //        $code = $this->request->getParam('code');
    //        $wishlistId = $this->request->getParam('wishlist_id');
    //        if (!$code && $wishlistId) {
    //            $code = \Magento\Framework\App\ObjectManager::getInstance()
    //                ->create(\Magento\Wishlist\Model\Wishlist::class)->load($wishlistId)->getCode();
    //        }
    //        return $code;
    //    }
}

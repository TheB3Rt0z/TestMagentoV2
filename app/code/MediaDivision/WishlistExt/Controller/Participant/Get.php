<?php

namespace MediaDivision\WishlistExt\Controller\Participant;

use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use MediaDivision\WishlistExt\Helper\ParticipantRepository;

class Get extends \Magento\Framework\App\Action\Action
{
    private $jsonFactory;
    private $session;
    private $participantRepository;
    private $emailCodeFactoryModel;
    private $wishlistItemFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        JsonFactory $jsonFactory,
        Session $session,
        ParticipantRepository $participantRepository,
        \Magento\Wishlist\Model\ItemFactory $wishlistItemFactory,
        \MediaDivision\WishlistExt\Model\CodeFactory $emailCodeFactoryModel
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->session = $session;
        $this->participantRepository = $participantRepository;
        $this->wishlistItemFactory = $wishlistItemFactory;
        $this->emailCodeFactoryModel = $emailCodeFactoryModel;
        return parent::__construct($context);
    }

    private function createResult($success, $data = [], $totals = [])
    {
        $result = $this->jsonFactory->create()->setData(['success' => $success, 'data' => $data, 'totals' => $totals]);
        if (!$success) {
            $result->setHttpResponseCode(400);
        }
        return $result;
    }

    public function execute()
    {
        $customerId = $this->session->getCustomerId();
        $request = $this->getRequest();
        $wishlistItemId = $request->getParam('wishlist_item_id');
        $code = $request->getParam('code');

        $participants = $this->participantRepository->getParticipants($wishlistItemId, $customerId, $code);

        if ($participants === false) {
            return $this->createResult(false);
        }

        $data = [];
        $totals = ['total_qty' => 0, 'available_qty' => 0];

        $wishlistItem = $this->wishlistItemFactory->create()->load($wishlistItemId);

        if ($participants) {
            $codeModel = false;
            if ($emailCode = $request->getParam('email_code')) {
                $codeModel = $this->emailCodeFactoryModel->create()->load($emailCode, 'code');
            }

            foreach ($participants as $participant) {
                $totals['total_qty'] += $participant->getQty();
                $totals['available_qty'] = $participant->getWiownerqty();

                if (($emailCode && !$codeModel->getId()) || ($codeModel && $codeModel->getId() !== $participant['code_id'])
                ) {
                    continue;
                }

                $data[] = [
                    'id' => $participant->getId(),
                    'name' => $participant->getFirstname() . ' ' . $participant->getLastname(),
                    'email' => $participant->getEmail(),
                    'qty' => number_format($participant->getQty(), 1, '.', ''),
                    'comment' => $participant->getComment() ? $participant->getComment() : ''
                ];
            }
            $totals['available_qty'] -= $totals['total_qty'];
        } else {
            $totals['total_qty'] = (int)$wishlistItem->getOwnerQty();
            $totals['available_qty'] = $wishlistItem->getOwnerQty() - (int)$wishlistItem->getOwnerQty();
        }
        $totals['total_qty'] = number_format($totals['total_qty'], 1, '.', '');
        $totals['available_qty'] = number_format($totals['available_qty'], 1, '.', '');


        return $this->createResult(true, $data, $totals);
    }
}

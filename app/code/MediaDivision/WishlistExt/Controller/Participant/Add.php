<?php

namespace MediaDivision\WishlistExt\Controller\Participant;

use MediaDivision\WishlistExt\Helper\ParticipantRepository;

class Add extends \Magento\Framework\App\Action\Action
{
    protected $_participantFactory;
    protected $_wishlistProvider;
    protected $_wishlistItemFactory;
    protected $_jsonFactory;
    protected $_session;
    protected $participantRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $session,
        ParticipantRepository $participantRepository
    ) {
        $this->_jsonFactory = $resultJsonFactory;
        $this->_session = $session;
        $this->participantRepository = $participantRepository;
        return parent::__construct($context);
    }

    public function execute()
    {
        try {
            $customerId = $this->_session->getCustomerId();
            $data = $this->getRequest()->getParams();
            $this->participantRepository->addParticipant($customerId, $data);
        } catch (\Exception $e) {
            return $this->_jsonFactory->create()->setData(['type' => 'error', 'text' => $e->getMessage()]);
        }
        return $this->_jsonFactory->create()->setData(['type' => 'success']);

        /*$request = $this->getRequest();
        $partName = $request->getParam('part_name');
        $partQuantity = $request->getParam('part_quantity');
        $wishlistItemId = $request->getParam('wishlist_item_id');
        $code = $request->getParam('code');

        return $this->createResult($this->participantRepository->addParticipant($wishlistItemId, $customerId, $code, $partName, $partQuantity));*/
    }

    //    private function createResult($success)
    //    {
    //        $result = $this->_jsonFactory->create()->setData(['success' => $success]);
    //        if (!$success) {
    //            $result->setHttpResponseCode(400);
    //        }
    //        return $result;
    //    }
}

<?php
namespace MediaDivision\WishlistExt\Controller\Participant;

use MediaDivision\WishlistExt\Helper\ParticipantRepository;

class Remove extends \Magento\Framework\App\Action\Action
{
    protected $jsonFactory;
    protected $session;
    protected $participantRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $session,
        ParticipantRepository $participantRepository
    ) {
        $this->jsonFactory = $resultJsonFactory;
        $this->session = $session;
        $this->participantRepository = $participantRepository;
        return parent::__construct($context);
    }

    private function createResult($success)
    {
        $result = $this->jsonFactory->create()->setData(['success' => $success]);
        if (!$success) {
            $result->setHttpResponseCode(400);
        }
        return $result;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $customerId = $this->session->getCustomerId();
        $success = $this->participantRepository->removeParticipant($id, $customerId);
        
        return $this->createResult($success);
    }
}

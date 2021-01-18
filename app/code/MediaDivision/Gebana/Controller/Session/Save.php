<?php

namespace MediaDivision\Gebana\Controller\Session;

use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Magento\Customer\Model\Session;

class Save extends \Magento\Framework\App\Action\Action
{

    private $resultJsonFactory;
    private $customerSession;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $customerSession
    ) {

        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->getRequest()->isAjax()) {
            $result = $this->resultJsonFactory->create();
            $post = $this->getRequest()->getParams();
            $this->customerSession->setConfigurableProductConfig($post);

            return $result->setData($post);
        }
    }
}

<?php

namespace MediaDivision\Campaigns\Controller\Adminhtml\Campaign;

class Saveorder extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Campaign';

    protected $resultPageFactory;
    protected $campaignFactory;
    protected $orderFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \MediaDivision\Campaigns\Model\CampaignFactory $campaignFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->campaignFactory = $campaignFactory;
        $this->orderFactory = $orderFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = $this->getRequest()->getPostValue('order_id');
        $campaignId = $this->getRequest()->getPostValue('campaign_id');

        if ($orderId && $campaignId) {
            try {
                $order = $this->orderFactory->create()->load($orderId);
                if ($order->getId()) {
                    $order->setCampaign($campaignId)->save();
                    $this->messageManager->addSuccess(__('Successfully saved the item.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        return $resultRedirect->setPath('sales/order');
    }
}

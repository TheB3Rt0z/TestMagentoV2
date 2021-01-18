<?php

namespace MediaDivision\Campaigns\Observer;

use MediaDivision\Campaigns\Helper\Data as CampaignHelper;

class SalesOrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{

    private $campaignHelper;
    private $request;

    public function __construct(CampaignHelper $campaignHelper, \Magento\Framework\App\RequestInterface $request)
    {
        $this->campaignHelper = $campaignHelper;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $requestData = $this->request->getPostValue();
        $order = $observer->getEvent()->getOrder();

        if (isset($requestData["order"]) && isset($requestData["order"]["campaign"]) && $requestData["order"]["campaign"]) {
            $order->setCampaign($requestData["order"]["campaign"])->save();
        } else {
            $this->campaignHelper->saveCampaign($order);
        }
        return $this;
    }
}

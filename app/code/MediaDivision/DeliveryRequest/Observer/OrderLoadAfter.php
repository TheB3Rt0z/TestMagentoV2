<?php

namespace MediaDivision\DeliveryRequest\Observer;

use \MediaDivision\Campaigns\Model\CampaignFactory;

class OrderLoadAfter implements \Magento\Framework\Event\ObserverInterface
{
    
    private $campaignFactory;
    
    public function __construct(CampaignFactory $campaignFactory)
    {
        $this->campaignFactory = $campaignFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->getOrderExtensionDependency();
        }

        $deliveryRequest = $order->getData('delivery_request');
        $extensionAttributes->setDeliveryRequest($deliveryRequest);

        $campaignCode = '';
        $campaign = $this->campaignFactory->create()->load($order->getData('campaign'));
        if ($campaign->getId()) {
            $campaignCode = $campaign->getCode();
        }
        $extensionAttributes->setCampaign($campaignCode);
        
        $extensionAttributes->setPresentGiftMessage($order->getData('present_gift_message'));

        $order->setExtensionAttributes($extensionAttributes);
    }

    private function getOrderExtensionDependency()
    {
        $orderExtension = \Magento\Framework\App\ObjectManager::getInstance()->get(
            '\Magento\Sales\Api\Data\OrderExtension'
        );

        return $orderExtension;
    }
}

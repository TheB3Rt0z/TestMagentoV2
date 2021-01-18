<?php

/* File: app/code/Atwix/OrderFeedback/Plugin/OrderRepositoryPlugin.php */

namespace MediaDivision\DeliveryRequest\Plugin;

use \MediaDivision\Campaigns\Model\CampaignFactory;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class OrderRepositoryPlugin
 */
class OrderRepositoryPlugin
{
    protected $extensionFactory;
    private $campaignFactory;

    public function __construct(CampaignFactory $campaignFactory, OrderExtensionFactory $extensionFactory)
    {
        $this->campaignFactory = $campaignFactory;
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Add "customer_feedback" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     *
     * @return OrderInterface
     */
    //    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order)
    //    {
    //        $customerFeedback = $order->getData(self::FIELD_NAME);
    //        $extensionAttributes = $order->getExtensionAttributes();
    //        $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
    //        $extensionAttributes->setCustomerFeedback($customerFeedback);
    //        $order->setExtensionAttributes($extensionAttributes);
    //
    //        return $order;
    //    }

    /**
     * Add "customer_feedback" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface   $subject
     * @param OrderSearchResultInterface $searchResult
     *
     * @return OrderSearchResultInterface
     */
    public function afterGetList(OrderRepositoryInterface $subject, OrderSearchResultInterface $searchResult)
    {
        $orders = $searchResult->getItems();

        foreach ($orders as &$order) {
            $orderExtensions = $order->getExtensionAttributes();
            $extensionAttributes = $orderExtensions ? $orderExtensions : $this->extensionFactory->create();

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

        return $searchResult;
    }
}

<?php

namespace MediaDivision\DeliveryRequest\Observer;

class SalesOrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{

    private $customerSession;
    private $request;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->customerSession = $customerSession;
        $this->request = $request;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $deliveryRequest = $this->customerSession->getDeliveryRequest();
        $this->customerSession->unsDeliveryRequest(); // sicherheitshalber aus der Session löschen, damit derselbe Wert nicht zweimal verwendet wird.
        if (!$deliveryRequest) {
            $deliveryRequest = $this->request->getPostValue('delivery_request');
        }
        if ($deliveryRequest) {
            $order->setDeliveryRequest($deliveryRequest)->save();
        }
        return $this;
    }
}

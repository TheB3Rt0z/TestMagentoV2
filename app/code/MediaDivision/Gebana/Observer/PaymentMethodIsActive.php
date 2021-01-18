<?php

namespace MediaDivision\Gebana\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Checkout\Model\CartFactory;
use Magento\Catalog\Model\ProductFactory;

class PaymentMethodIsActive implements ObserverInterface
{

    private $cartFactory;
    private $creditCardMethods = [
        'braintree',
        'aw_sarp_braintree_recurring',
        'saferpaycw_mastercard',
        'saferpaycw_visa',
        'saferpaycw_americanexpress',
        'saferpaylnr_creditcard',
        'saferpaycw_creditcard',
        'banktransfer',
        'purchaseorder',
        //'klarna_kp',
    ];
    private $productFactory;

    public function __construct(
        CartFactory $cartFactory,
        ProductFactory $productFactory
    ) {
        $this->cartFactory = $cartFactory;
        $this->productFactory = $productFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $creditCartRequired = false;
        $event = $observer->getEvent();
        $method = $event->getMethodInstance();
        $result = $event->getResult();
        $cart = $this->cartFactory->create();
        $abo = false;
        $items = $cart->getQuote()->getAllItems();
        foreach ($items as $item) {
            $product = $this->productFactory->create()->load($item->getProductId());

            if ($product->getData('aw_sarp2_subscription_type') > 1) {
                $abo = true;
            }
            if ($product->getCreditCardRequired()) {
                $creditCartRequired = true;

                if ($method->getCode() != 'saferpaycw_creditcard')  {
                    $result->setData('is_available', false);
                }

            }
        }

        if ($creditCartRequired && !in_array($method->getCode(), $this->creditCardMethods)) {
            $result->setData('is_available', false);

        }
        if (($method->getCode() == 'saferpaylnr_creditcard') && !$abo) {
            $result->setData('is_available', false);
        }


        return $this;
    }
}

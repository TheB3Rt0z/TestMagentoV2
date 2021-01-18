<?php

namespace MediaDivision\Gebana\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Store\Model\StoreManagerInterface;

class Price extends AbstractHelper
{
    protected $storeManager;

    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getCurrency()
    {
        return $this->storeManager->getStore()->getCurrentCurrency();
    }
}

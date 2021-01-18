<?php

/**
 * @author    Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package   Amasty_Xnotif
 */

namespace MediaDivision\Xnotif\Block\Adminhtml\Catalog\Product\Edit\Tab\Alerts\Renderer;

use Magento\Framework\DataObject;

/**
 * Class FirstName
 */
class Store extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    private $storeManager;

    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    public function render(DataObject $row)
    {
        $store = $this->storeManager->getStore($row->getStoreId());
        return $store->getCode();
    }
}

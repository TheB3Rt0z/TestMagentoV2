<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MediaDivision\Gebana\Block\Adminhtml\Order\Create\Search\Grid\Renderer;

use \Magento\Backend\Block\Context;
use \Magento\InventoryCatalogAdminUi\Model\GetSourceItemsDataBySku;

/**
 * Adminhtml sales create order product search grid product name column renderer
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Quantity extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    private $sourceDataBySku;
    
    public function __construct(GetSourceItemsDataBySku $sourceDataBySku, Context $context, $data = [])
    {
        $this->sourceDataBySku = $sourceDataBySku;
        parent::__construct($context, $data);
    }

    /**
     * Render product name to add Configure link
     *
     * @param  \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $rendered = parent::render($row);
        $data = $this->sourceDataBySku->execute($row->getSku());
        $result = "";
        foreach ($data as $source) {
            $result .= "<p>".$source["name"].": ".$source["quantity"]."</p>";
        }
        return $result;
    }
}

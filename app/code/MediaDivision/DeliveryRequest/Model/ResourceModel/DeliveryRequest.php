<?php
            
namespace MediaDivision\DeliveryRequest\Model\ResourceModel;

class DeliveryRequest extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context)
    {
        parent::__construct($context);
    }
    
    protected function _construct()
    {
        $this->_init('md_delivery_request', 'id');
    }
}

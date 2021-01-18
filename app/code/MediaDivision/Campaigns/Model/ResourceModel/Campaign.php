<?php
            
namespace MediaDivision\Campaigns\Model\ResourceModel;

class Campaign extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    
    public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context)
    {
        parent::__construct($context);
    }
    
    protected function _construct()
    {
        $this->_init('md_campaign', 'id');
    }
}

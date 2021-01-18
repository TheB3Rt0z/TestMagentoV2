<?php
            
namespace MediaDivision\Campaigns\Model\ResourceModel\Campaign;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'md_campaign_collection';
    protected $_eventObject = 'campaign_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MediaDivision\Campaigns\Model\Campaign', 'MediaDivision\Campaigns\Model\ResourceModel\Campaign');
    }
}

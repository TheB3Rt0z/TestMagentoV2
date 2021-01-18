<?php
            
namespace MediaDivision\DeliveryRequest\Model\ResourceModel\DeliveryRequest;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'md_delivery_request_collection';
    protected $_eventObject = 'deliveryrequest_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('MediaDivision\DeliveryRequest\Model\DeliveryRequest', 'MediaDivision\DeliveryRequest\Model\ResourceModel\DeliveryRequest');
    }
}

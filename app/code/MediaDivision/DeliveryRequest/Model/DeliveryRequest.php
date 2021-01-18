<?php
namespace MediaDivision\DeliveryRequest\Model;
    
class DeliveryRequest extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'md_delivery_request';

    protected $_cacheTag = 'md_delivery_request';
    protected $_eventPrefix = 'md_delivery_request';

    protected function _construct()
    {
        $this->_init('MediaDivision\DeliveryRequest\Model\ResourceModel\DeliveryRequest');
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getDefaultValues()
    {
        $values = [];

        return $values;
    }
}

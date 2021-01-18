<?php
namespace MediaDivision\Campaigns\Model;
    
class Campaign extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'md_campaign';

    protected $_cacheTag = 'md_campaign';
    protected $_eventPrefix = 'md_campaign';

    protected function _construct()
    {
        $this->_init('MediaDivision\Campaigns\Model\ResourceModel\Campaign');
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

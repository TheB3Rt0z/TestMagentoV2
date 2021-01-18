<?php

namespace MediaDivision\WishlistExt\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Code extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'md_wishlistext_code';

    protected $_cacheTag = 'md_wishlistext_code';

    public function _construct()
    {
        $this->_init('MediaDivision\WishlistExt\Model\ResourceModel\Code');
        $this->_collectionName = 'MediaDivision\WishlistExt\Model\ResourceModel\CodeCollection';
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}

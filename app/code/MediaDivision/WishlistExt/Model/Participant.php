<?php

namespace MediaDivision\WishlistExt\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class Participant extends AbstractModel implements IdentityInterface
{

    const CACHE_TAG = 'md_wishlistext_participant';

    protected $_cacheTag = 'md_wishlistext_participant';

    public function _construct()
    {
        $this->_init('MediaDivision\WishlistExt\Model\ResourceModel\Participant');
        $this->_collectionName = 'MediaDivision\WishlistExt\Model\ResourceModel\ParticipantCollection';
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }
}

<?php

namespace MediaDivision\WishlistExt\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Participant extends AbstractDb
{

    protected function _construct()
    {
        $this->_init('md_wishlistext_participant', 'id');
    }
}

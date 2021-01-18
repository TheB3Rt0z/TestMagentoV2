<?php

namespace MediaDivision\WishlistExt\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Code extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('md_wishlistext_code', 'id');
    }
}

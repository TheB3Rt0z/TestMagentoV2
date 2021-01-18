<?php

namespace MediaDivision\WishlistExt\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class CodeCollection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('MediaDivision\WishlistExt\Model\Code', 'MediaDivision\WishlistExt\Model\ResourceModel\Code');
    }
}

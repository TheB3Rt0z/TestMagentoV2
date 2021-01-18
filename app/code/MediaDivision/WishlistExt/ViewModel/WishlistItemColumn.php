<?php

namespace MediaDivision\WishlistExt\ViewModel;

use Magento\Catalog\Block\Product\View;
use Magento\Framework\App\ObjectManager;

class WishlistItemColumn implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    private $productView;

    public function __construct()
    {
        $this->productView = ObjectManager::getInstance()->get(View::class);
    }

    public function getOwnerQty(\Magento\Wishlist\Model\Item $item)
    {
        $qty = $item->getQty();
        $ownerQty = $item->getOwnerQty();
        $ownerQty = $ownerQty < $this->productView->getProductDefaultQty($item->getProduct())
            ? $this->productView->getProductDefaultQty($item->getProduct()) : $ownerQty;
        $ownerQty = $ownerQty ?: 1;
        return $ownerQty > $qty ? $qty : $ownerQty;
    }

    public function getShareOwnerQty($item)
    {
        $qty = $item->getOwnerQty() * 1;
        if (!$qty) {
            $qty = 1;
        }
        return $qty;
    }
}

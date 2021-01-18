<?php

namespace MediaDivision\WishlistExt\Plugin;

class UpdateWishlist
{
    private $wishlistProvider;
    private $quantityProcessor;
    private $_objectManager;

    public function __construct(
        \Magento\Wishlist\Controller\WishlistProviderInterface $wishlistProvider,
        \Magento\Wishlist\Model\LocaleQuantityProcessor $quantityProcessor,
        \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->quantityProcessor = $quantityProcessor;
        $this->_objectManager = $objectmanager;
    }

    public function beforeExecute($subject)
    {
        $wishlist = $this->wishlistProvider->getWishlist();
        $post = $subject->getRequest()->getPostValue();
        /*
        if ($post && isset($post['owner_qty']) && is_array($post['owner_qty'])) {
            foreach ($post['owner_qty'] as $itemId => $ownerQty) {
                $item = $this->_objectManager->create(\Magento\Wishlist\Model\Item::class)->load($itemId);
                if ($item->getWishlistId() != $wishlist->getId()) {
                    continue;
                }

                $qty = $this->quantityProcessor->process($ownerQty);
                if (isset($post['qty'][$itemId]) && $qty > $post['qty'][$itemId]) {
                    $qty = $post['qty'][$itemId];
                }
                $item->setOwnerQty($qty)->save();
            }
        } */

        $orderDate = $subject->getRequest()->getParam('order_date');
        $comment = $subject->getRequest()->getParam('comment');
        $wishlist->setOrderDate($orderDate)->setComment($comment)->save();

        return $subject;
    }
}

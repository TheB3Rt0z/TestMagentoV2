<?php

namespace MediaDivision\WishlistExt\Plugin;

class WishlistItem
{
    private $participantFactory;

    public function __construct(
        \MediaDivision\WishlistExt\Model\ParticipantFactory $participantFactory
    ) {
        $this->participantFactory = $participantFactory;
    }

    public function beforeAddToCart($wishlistItem, $cart, $delete)
    {
        if ($delete === true) {
            $delete = null;
        }
        return [$cart, $delete];
    }

    public function afterAddToCart($wishlistItem, $response, $cart, $delete)
    {
        $participants = $this->participantFactory->create()->getResourceCollection()
            ->addFieldToFilter('wishlist_item_id', ['eq' => $wishlistItem->getId()]);

        if ($participants->count()) {
            $product = $wishlistItem->getProduct();
            $quoteItem = $cart->getQuote()->getItemByProduct($product);

            $quoteItem->setWishlistParticipants(json_encode($participants->toArray()))->save();
        }
        if ($delete === null) {
            $wishlistItem->delete();
        }
        return $response;
    }

    public function beforeBeforeSave($wishlistItem)
    {
        $amount = $wishlistItem->getProduct()->getBasePriceAmount();
        $qty = $wishlistItem->getQty();
        $newQty = $amount * $qty;
        $wishlistItem->setOwnerQty($newQty);

        return $wishlistItem;
    }
}

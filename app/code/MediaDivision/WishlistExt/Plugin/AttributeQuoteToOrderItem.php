<?php

namespace MediaDivision\WishlistExt\Plugin;

class AttributeQuoteToOrderItem
{
    public function aroundConvert(
        \Magento\Quote\Model\Quote\Item\ToOrderItem $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $additional = []
    ) {
        /**
*
         *
 * @var $orderItem \Magento\Sales\Model\Order\Item
*/
        $orderItem = $proceed($item, $additional);
        $orderItem->setWishlistParticipants($item->getWishlistParticipants());
        return $orderItem;
    }
}

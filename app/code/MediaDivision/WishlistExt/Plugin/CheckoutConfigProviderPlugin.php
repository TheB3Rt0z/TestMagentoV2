<?php

namespace MediaDivision\WishlistExt\Plugin;

class CheckoutConfigProviderPlugin
{
    private $quoteItemFactory;

    public function __construct(
        \Magento\Quote\Model\Quote\ItemFactory $quoteItemFactory
    ) {
        $this->quoteItemFactory = $quoteItemFactory;
    }

    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, array $result)
    {
        $items = $result['totalsData']['items'];

        foreach ($items as &$item) {
            $quoteItem = $this->quoteItemFactory->create()->load($item['item_id']);
            $participants = $quoteItem->getWishlistParticipants();
            if ($participants) {
                $participants = json_decode($participants, true);
            }
            $item['participants'] = $participants ? $participants['items'] : [];
        }
        $result['totalsData']['items'] = $items;

        return $result;
    }
}

<?php

namespace MediaDivision\WishlistExt\Model\Quote\Total;

use Magento\Framework\DataObject\Copy;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Api\Data\TotalsItemInterface;
use Magento\Quote\Api\Data\TotalsItemExtensionFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;

class Modifier
{
    private $quoteRepository;
    private $totalsItemExtensionFactory;

    public function __construct(
        CartRepositoryInterface $quoteRepository,
        TotalsItemExtensionFactory $totalsItemExtensionFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->totalsItemExtensionFactory = $totalsItemExtensionFactory;
    }

    public function modify(TotalsInterface $totals, $cartId)
    {
        $quote = $this->quoteRepository->get($cartId);
        foreach ($totals->getItems() as $item) {
            $itemId = $item->getItemId();
            $quoteItem = $quote->getItemById($itemId);
            $this->modifyItem($item, $quoteItem);
        }
        return $totals;
    }

    private function modifyItem(&$item, $quoteItem)
    {
        $participants = $quoteItem->getWishlistParticipants();
        if ($participants) {
            $participants = json_decode($participants, true);
        }
        $participants = $participants ? $participants['items'] : [];

        $totalsItemExtension = $item->getExtensionAttributes();
        if ($totalsItemExtension === null) {
            $totalsItemExtension = $this->totalsItemExtensionFactory->create();
        }
        $totalsItemExtension->setParticipants($participants);
        $item->setExtensionAttributes($totalsItemExtension);

        return $item;
    }
}

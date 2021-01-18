<?php

namespace MediaDivision\WishlistExt\Plugin;

use MediaDivision\WishlistExt\Model\Quote\Total\Modifier;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;

class QuoteTotalRepository
{
    private $modifier;

    public function __construct(Modifier $modifier)
    {
        $this->modifier = $modifier;
    }

    public function afterGet(CartTotalRepositoryInterface $subject, TotalsInterface $totals, $cartId)
    {
        return $this->modifier->modify($totals, $cartId);
    }
}

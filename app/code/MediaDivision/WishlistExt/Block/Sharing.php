<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Wishlist customer sharing block
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */

namespace MediaDivision\WishlistExt\Block;

use Magento\Captcha\Block\Captcha;
use Magento\Wishlist\Controller\WishlistProviderInterface;

/**
 * Class Sharing
 *
 * @api
 * @since   100.0.2
 * @package Magento\Wishlist\Block\Customer
 */
class Sharing extends \Magento\Wishlist\Block\Customer\Sharing //\Magento\Framework\View\Element\Template
{
    protected $wishlistProvider;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Wishlist\Model\Config                   $wishlistConfig
     * @param \Magento\Framework\Session\Generic               $wishlistSession
     * @param array                                            $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Wishlist\Model\Config $wishlistConfig,
        \Magento\Framework\Session\Generic $wishlistSession,
        WishlistProviderInterface $wishlistProvider,
        array $data = []
    ) {
        parent::__construct($context, $wishlistConfig, $wishlistSession, $data);
        $this->wishlistProvider = $wishlistProvider;
    }

    public function getSharingCode()
    {
        return $this->wishlistProvider->getWishlist()->getSharingCode();
    }

    public function getSendUrl()
    {
        $url = parent::getSendUrl();
        $wishlistId = $this->getRequest()->getParam('wishlist_id');
        if ($wishlistId) {
            $url .= "wishlist_id/$wishlistId";
        }
        return $url;
    }
}

<?php

namespace MediaDivision\Gebana\Observer;

use Magento\Framework\Event\ObserverInterface;

class ForceStorecodeRedirect implements ObserverInterface
{

    protected $storeManager;
    protected $url;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->storeManager = $storeManager;
        $this->url = $url;
        $this->storeCodes = array_keys($this->storeManager->getStores(false, true));
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $url = $this->url->getCurrentUrl(false);
        //$urlParts = parse_url($this->url->getCurrentUrl());
        //$path = $urlParts['path'];
        //file_put_contents('test.txt', "Test: " . $this->url->getCurrentUrl() . "\n", FILE_APPEND);
        if (preg_match('?www.gebana.com/?', $url) && !preg_match('?www.gebana.com/..-../?', $url) && !preg_match('?www.gebana.com/chreseller/?', $url)) {
            $redirectUrl = preg_replace('?(www.gebana.com/)?', 'www.gebana.com/shop/de-ch/', $url);
            //file_put_contents('test.txt', "Redirect: " . $redirectUrl . "\n", FILE_APPEND);
            // Redirect to URL including storecode
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $redirectUrl);
            exit();
        }
    }
}

<?php

namespace MediaDivision\GoogleTagManager\Block;

use \Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Template extends \Magento\Framework\View\Element\Template
{

    private $scopeConfig;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    public function getActive()
    {
        return $this->scopeConfig->getValue('googletagmanager/google_tag_manager/gtm_active');
    }

    public function getContainerId()
    {
        return $this->scopeConfig->getValue('googletagmanager/google_tag_manager/gtm_container_id');
    }
}

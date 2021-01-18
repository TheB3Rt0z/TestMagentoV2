<?php

namespace MediaDivision\Campaigns\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\SalesRule\Model\RuleFactory;

class Data extends AbstractHelper
{

    private $ruleFactory;

    public function __construct(RuleFactory $ruleFactory)
    {
        $this->ruleFactory = $ruleFactory;
    }

    public function saveCampaign($order)
    {
        // Wenn eine Order einen Rabattcode hat, überprüfen, ob der Rabattcode zu einer Kampagne gehört.
        // Wenn ja, Kampagne in der Order speichern.
        $ruleIds = $order->getAppliedRuleIds();
        $orderCampaign = "";
        if ($ruleIds) {
            foreach (explode(',', $ruleIds) as $item) {
                $rule = $this->ruleFactory->create()->load($item);
                $campaignId = $rule->getCampaign();
                if ($campaignId) {
                    $orderCampaign = $campaignId;
                }
            }
        }
        if ($orderCampaign) {
            $order->setCampaign($orderCampaign)->save();
        }
    }
}

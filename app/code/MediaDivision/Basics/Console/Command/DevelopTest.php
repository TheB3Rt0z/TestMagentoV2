<?php

namespace MediaDivision\Basics\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DevelopTest extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('develop:test')
            ->setDescription('Template für ein Test-Programm');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $state = $objectManager->get('\Magento\Framework\App\State');
        $state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND); // or \Magento\Framework\App\Area::AREA_ADMINHTML, depending on your needs
        $productAlert = $objectManager->create('\Magento\ProductAlert\Model\Observer');
        $productAlert->process();
        //$order = $objectManager->create('Magento\Sales\Model\Order')->load(536);
        //$coupon = $objectManager->create('Magento\SalesRule\Model\Coupon')->loadByCode($order->getCouponCode());
        //print_r($coupon->getData());
        //$helper = $objectManager->create('MediaDivision\Campaigns\Helper\Data')->saveCampaign($order);
    }
}

<?php

namespace MediaDivision\Gebana\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use MediaDivision\Gebana\Helper\Customer as CustomerHelper;

class CustomerCleverreach extends Command
{


    const DEBUG = "debug";

    private $debug = false;
    private $customerHelper;
    
    public function __construct(CustomerHelper $customerHelper, $name = null)
    {
        $this->customerHelper = $customerHelper;
        parent::__construct($name);
    }
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(self::DEBUG, "d", InputOption::VALUE_OPTIONAL, "debug")
        ];
        $this->setName("customer:cleverreach")
            ->setDescription("Kunden zu Cleverreach übertragen")->setDefinition($options);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption(self::DEBUG)) {
            $this->debug = true;
            echo "\nSetze debug mode.\n\n";
        }

        if ($this->debug) {
            echo "\n\nStarte customer:cleverreach\n\n";
        }
        $this->customerHelper->customerToCleverreach($this->debug);
    }
}

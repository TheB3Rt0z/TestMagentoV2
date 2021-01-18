<?php

namespace MediaDivision\Gebana\Console\Command;

use MediaDivision\Magmi\Helper\Data as Magmi;
use MediaDivision\Gebana\Helper\Import as Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\Filesystem\DirectoryList;

class ImportProducts extends Command
{

    const DEBUG = "debug";

    private $debug = false;
    private $helper;
    private $magmi;
    private $installDir;

    public function __construct(Helper $helper, Magmi $magmi, DirectoryList $directoryList, $name = null)
    {
        $this->helper = $helper;
        $this->magmi = $magmi;
        $this->installDir = $directoryList->getRoot();
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
        $this->setName("import:products")
            ->setDescription("Produkte importieren")->setDefinition($options);
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
            echo "\n\nStarte import:products\n\n";
        }

        $csvData = $this->helper->importProducts($this->debug);

        if (isset($csvData[0])) {
            if ($this->debug) {
                echo "\n\nSchreibe Magmi-Datei\n\n";
            }
            $this->magmi->writeMagmiFile(array_keys($csvData[0]), $csvData);
            if ($this->debug) {
                echo "Starte Magmi\n\n";
            }
            $this->magmi->import("Products", "create");
            if ($this->debug) {
                echo count($csvData) . " Produkte importiert.\n";
            }
        } else {
            if ($this->debug) {
                echo "\nkeine Produkte zu importieren.\n";
            }
        }
    }
}

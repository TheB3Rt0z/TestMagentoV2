<?php

namespace MediaDivision\Basics\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Aheadworks\Sarp2\Engine\EngineInterface;
use Magento\Framework\App\State as AppState;

class DevelopSarp2Checkshipping extends Command
{

    const DEBUG = "debug";

    private $debug = false;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var EngineInterface
     */
    private $engine;

    /**
     * @param EngineInterface $engine
     */
    public function __construct(AppState $appState, EngineInterface $engine)
    {
        $this->engine = $engine;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(self::DEBUG, "d", InputOption::VALUE_OPTIONAL, "debug")
        ];
        $this->setName("develop:sarp2:checkshipping")
            ->setDescription("Nutzung der Liefermethode im Abo-Modul prüfen")->setDefinition($options);
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
            echo "\n\nStarte develop:sarp2:checkshipping\n\n";
        }
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->engine->processPaymentsForToday();
    }
}

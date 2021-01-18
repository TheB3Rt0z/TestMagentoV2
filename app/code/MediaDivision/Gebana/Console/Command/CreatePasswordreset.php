<?php

// https://rematiptop.flyte228.lcube-server.de/de/customer/account/createPassword/?id=1&token=B1CrZZ9NReYDdYjFcPwvLi6p5anWURbS

namespace MediaDivision\Gebana\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\Math\Random;
use \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use \Magento\Customer\Api\CustomerRepositoryInterface;
use \Magento\Store\Model\StoreManagerInterface;

class CreatePasswordreset extends Command
{

    const DEBUG = "debug";

    private $debug = false;
    private $accountManagement;
    private $mathRandom;
    private $customerCollectionFactory;
    private $customerRepository;
    private $storeManager;
    private $url = 'https://gebana.ch/';
    private $urlPath = 'customer/account/createPassword/';
    private $language = [
        '0' => 'de',
        '1' => 'de',
        '3' => 'se',
        '4' => 'de',
        '5' => 'de',
        '7' => 'fr',
    ];

    public function __construct(
        AccountManagement $accountManagement,
        Random $random,
        CustomerCollectionFactory $customerCollectionFactory,
        CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->accountManagement = $accountManagement;
        $this->mathRandom = $random;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->customerRepository = $customerRepositoryInterface;
        $this->storeManager = $storeManager;
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
        $this->setName("create:passwordresetlinks")
            ->setDescription("Erzeugt für die Kunden einen Passwort-ResetLink")->setDefinition($options);
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
            echo "\n\nStarte create:passwordresetlinks\n\n";
        }
        echo "Email;Vorname;Nachname;Sprache;Reset-Link\n";
        $customerCollection = $this->customerCollectionFactory->create()->addAttributeToSelect('*');
        //        $customerCollection->addFieldToFilter('email',['in' => [
        //'me@davidklier.ch',
        //'lindagrosskreuz@gmail.com',
        //'sandra_duetschler@gmx.ch',
        //'catherine.keith@gmail.com',
        //'stefan.lanz@gmx.ch',
        //        ]]);
        foreach ($customerCollection as $customer) {
            $store = $this->getCalculatedStore($customer);
            echo $customer->getEmail() . ";";
            echo $customer->getFirstname() . ";";
            echo $customer->getLastname() . ";";
            echo $store->getCode() . ";";
            $token = $this->initiatePasswordReset($customer);
            echo $store->getUrl() . $this->urlPath .  '?id=' . $customer->getId() . '&token=' . $token . "\n";
        }
    }

    private function getCalculatedStore($customer)
    {
        $storeId = $customer->getStoreId();
        if ($storeId == 0) {
            $storeId = 1;
        }
        $store = $this->storeManager->getStore($storeId);
        return $store;
    }
    
    public function initiatePasswordReset($customer)
    {
        // No need to validate customer address while saving customer reset password token
        //$this->accountManagement->disableAddressValidation($customer);

        $newPasswordToken = $this->mathRandom->getUniqueHash();
        $apiCustomer = $this->customerRepository->getById($customer->getId());
        $this->accountManagement->changeResetPasswordLinkToken($apiCustomer, $newPasswordToken);
        return $newPasswordToken;
    }
}

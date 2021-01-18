<?php

namespace MediaDivision\Gebana\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\Filesystem\DirectoryList;
use Magento\Customer\Model\CustomerFactory;
use \Magento\Customer\Model\AddressFactory;
use Magento\Framework\App\ResourceConnection;
use \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use CleverReach\CleverReachIntegration\IntegrationCore\BusinessLogic\Sync\RecipientSyncTask;
use CleverReach\CleverReachIntegration\Services\BusinessLogic\RecipientService;
use CleverReach\CleverReachIntegration\Helper\InitializerInterface;
use CleverReach\CleverReachIntegration\IntegrationCore\BusinessLogic\Entity\Tag;
use CleverReach\CleverReachIntegration\IntegrationCore\BusinessLogic\Entity\TagCollection;
use CleverReach\CleverReachIntegration\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\CleverReachIntegration\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\CleverReachIntegration\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\CleverReachIntegration\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\CleverReachIntegration\IntegrationCore\Infrastructure\TaskExecution\Queue;
use CleverReach\CleverReachIntegration\IntegrationCore\Infrastructure\TaskExecution\Task;
use CleverReach\CleverReachIntegration\IntegrationCore\Infrastructure\Utility\Serializer;
use CleverReach\CleverReachIntegration\Services\Infrastructure\ConfigService;

class Customer extends AbstractHelper
{

    /**
     * @var ConfigService $configService
     */
    private $configService;
    private $debug;
    private $customersFile = "/var/import/customers.csv";
    private $customersCleverReachFile = "/var/import/customers_cleverreach.csv";
    private $adressesFile = "/var/import/addresses.csv";
    private $addressFactory;
    private $customerFactory;
    private $customerCollectionFactory;
    private $initializer;
    private $installDir;
    private $resource;
    private $subscriberFactory;
    private $gender = [
        '' => 1,
        1007 => 1, // male
        1008 => 2, // female
    ];
    // Zuweisung alte Website-ID -> neue Website-ID core_website -> store_website
    private $newWebsiteList = [
        0 => 0, // Admin -> Admin
        2 => 4, // eu.shop -> eu.shop
        3 => 1, // ch.shop -> ch.shop
        4 => 1, // vkmb -> ch.shop
        5 => 2, // Reseller -> Reseller
        6 => 3, // se.shop -> se.shop
    ];
    // Zuweisung alter Store -> neuer Store
    private $newStore = [
        0 => 0,
        1 => 1,
        3 => 4,
        4 => 2,
        5 => 7,
        6 => 1,
        7 => 7,
        8 => 5,
        9 => 2,
        10 => 3,
    ];
    // Zuweisung alte Gruppen-ID -> neue Gruppen-ID
    private $newCustomerGroup = [
        0 => 0,
        1 => 1,
        3 => 3,
        4 => 23,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
        9 => 9,
        10 => 10,
        11 => 11,
        12 => 12,
        13 => 13,
        14 => 14,
        15 => 15,
        16 => 16,
        17 => 17,
        18 => 18,
    ];

    public function __construct(
        AddressFactory $addressFactory,
        Context $context,
        CustomerFactory $customerFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        DirectoryList $directoryList,
        ResourceConnection $resource,
        SubscriberFactory $subscriberFactory,
        InitializerInterface $initializer
    ) {
        $this->addressFactory = $addressFactory;
        $this->customerFactory = $customerFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->installDir = $directoryList->getRoot();
        $this->initializer = $initializer;
        $this->resource = $resource;
        $this->subscriberFactory = $subscriberFactory;

        parent::__construct($context);
    }

    public function insertCustomers($debug, $anonymise)
    {
        $write = $this->resource->getConnection();

        if (($chandle = fopen($this->installDir . $this->customersFile, "r")) !== false) {
            $head = fgetcsv($chandle, 0, ";");
            $write->query("delete from customer_entity", []);
            //$write->query("delete from customer_entity where entity_id > 999", []);
            while (($line = fgetcsv($chandle, 0, ";")) !== false) {
                $data = [];
                foreach ($head as $index => $field) {
                    $data[$field] = $line[$index];
                }
                if ($data["entity_id"] < 1000) {
                    //continue; // Für Testzwecke die ersten 200 IDs übergehen, damit die Testuser nicht überschrieben werden.
                }
                $email = $data["email"];
                if ($anonymise) {
                    $email = "kunde" . $data["entity_id"] . "@gebana.com";
                }
                // dob?
                $query = 'insert into customer_entity '
                        . '(entity_id,website_id,email,group_id,store_id,created_at,updated_at,is_active,disable_auto_group_change,' // 9
                        . 'created_in,prefix,firstname,middlename,lastname,suffix,default_billing,default_shipping,gender) VALUES '
                        . '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                try {
                    $write->query(
                        $query,
                        [
                        $data["entity_id"],
                        $this->newWebsiteList[$data["website_id"]],
                        $email,
                        isset($this->newCustomerGroup[$data["group_id"]]) ? $this->newCustomerGroup[$data["group_id"]] : 0,
                        isset($this->newStore[$data["store_id"]]) ? $this->newStore[$data["store_id"]] : 0,
                        $data["created_at"],
                        $data["updated_at"],
                        $data["is_active"],
                        $data["disable_auto_group_change"],
                        "Admin", // created_in
                        $data["prefix"],
                        $data["firstname"],
                        $data["middlename"],
                        $data["lastname"],
                        $data["suffix"],
                        $data["default_billing"],
                        $data["default_shipping"],
                        $this->gender[$data["gender"]],
                        ]
                    );
                } catch (\Exception $ex) {
                    echo $ex->getMessage() . "\n";
                    //print_r($data);
                }
                //print_r($data);
            }
            fclose($chandle);
        }

        if (($ahandle = fopen($this->installDir . $this->adressesFile, "r")) !== false) {
            $head = fgetcsv($ahandle, 0, ";");
            $write->query("delete from customer_address_entity", []);
            //$write->query("delete from customer_address_entity where parent_id > 999", []);
            while (($line = fgetcsv($ahandle, 0, ";")) !== false) {
                $data = [];
                foreach ($head as $index => $field) {
                    $data[$field] = $line[$index];
                }
                if ($data["parent_id"] < 1000) {
                    //continue; // Für Testzwecke die ersten 200 IDs übergehen, damit die Testuser nicht überschrieben werden.
                }
                // dob?, gender?, default_shipping?
                $query = 'insert into customer_address_entity '
                        . '(entity_id,parent_id,created_at,updated_at,is_active,city,company,country_id,fax,firstname,lastname,' // 11
                        . 'middlename,postcode,prefix,region,region_id,street,suffix,telephone) VALUES '
                        . '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                try {
                    $write->query(
                        $query,
                        [
                        $data["entity_id"],
                        $data["parent_id"],
                        $data["created_at"],
                        $data["updated_at"],
                        $data["is_active"],
                        $data["city"],
                        $data["company"],
                        $data["country_id"],
                        $data["fax"],
                        $data["firstname"],
                        $data["lastname"],
                        $data["middlename"],
                        $data["postcode"],
                        $data["prefix"],
                        $data["region"],
                        $data["region_id"],
                        $data["street"],
                        $data["suffix"],
                        $data["telephone"]
                        ]
                    );
                } catch (\Exception $ex) {
                    echo $ex->getMessage() . "\n";
                }
            }
            fclose($ahandle);
        }
    }

    /**
     * Mögliche Events:
     * customer_register_success        CleverReach\CleverReachIntegration\Observer\CustomerRegisteredObserver
     * customer_save_after              CleverReach\CleverReachIntegration\Observer\CustomerSavedByAdminObserver
     * newsletter_subscriber_save_after CleverReach\CleverReachIntegration\Observer\RecipientSubscribedObserver
     *
     * @param type $debug
     */
    public function customerToCleverreach($debug)
    {
        $this->debug = $debug;
        $shopList = [
            "ch-de" => 1,
            "ch-fr" => 7,
            "de-de" => 4,
            "se-sv" => 3
        ];
        $websiteList = [
            "ch-de" => 1,
            "ch-fr" => 1,
            "de-de" => 4,
            "se-sv" => 3
        ];

        $emailList = $this->getCleverReachEmails();
        if ($this->debug) {
            echo "CustomerToCleverreach\n";
            echo "Gleiche mit " . count($emailList) . " Email-Adressen ab.\n";
        }
        $collection = $this->customerCollectionFactory
            ->create()
            ->addAttributeToSelect('store_id')
            ->addAttributeToSelect('email')
        //->addAttributeToFilter('email', 'Sara.sundin@hotmail.se')
        ;
        
        $shopEmailList = [];
        foreach ($collection as $customer) {
            $shopEmailList[] = $customer->getEmail();
            if (in_array($customer->getEmail(), array_keys($emailList))) {
                $fullCustomer = $this->customerFactory->create()->load($customer->getId());
                $cStoreId = $fullCustomer->getStoreId();
                $cWebsiteId = $fullCustomer->getWebsiteId();
                $gStoreId = $shopList[$emailList[$fullCustomer->getEmail()]];
                $gWebsiteId = $websiteList[$emailList[$fullCustomer->getEmail()]];
                if (($cStoreId != $gStoreId) || ($cWebsiteId != $gWebsiteId)) {
                    try {
                        $fullCustomer->setWebsiteId($gWebsiteId)->setStoreId($gStoreId)->save();
                    } catch (\Exception $ex) {
                        echo $ex->getMessage() . "\n";
                    }
                    echo $fullCustomer->getEmail() . " " . $cWebsiteId . " " . $cStoreId . " " . $gStoreId . " " . $gWebsiteId . "\n";
                }

                //$this->insertOrUpdate($customer);
                //$this->syncWithCleverreach($customer->getId());
            }
        }
        
        //        foreach (array_keys($emailList) as $email) {
        //            if(!in_array($email, $shopEmailList)) {
        //                echo $email . "\n";
        //            }
        //        }
    }

    private function getCleverReachEmails()
    {
        $emailList = [];
        if (($ahandle = fopen($this->installDir . $this->customersCleverReachFile, "r")) !== false) {
            $head = fgetcsv($ahandle, 0, ";");
            while (($line = fgetcsv($ahandle, 0, ";")) !== false) {
                $data = [];
                foreach ($head as $index => $field) {
                    $data[$field] = $line[$index];
                }
                $emailList[$data["email"]] = $data["Shop"];
            }
            fclose($ahandle);
        }
        return $emailList;
    }

    private function insertOrUpdate($customer)
    {
        $write = $this->resource->getConnection();
        $isInTable = $write->fetchOne("SELECT count(*) as count FROM newsletter_subscriber WHERE customer_id=?", [$customer->getId()]);
        if ($isInTable) {
            $query = 'update newsletter_subscriber set change_status_at=?, subscriber_status=? where customer_id=?';
            try {
                $write->query(
                    $query,
                    [
                    date('Y-m-d H:i:s'),
                    \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED,
                    $customer->getId()
                    ]
                );
            } catch (\Exception $ex) {
                echo $ex->getMessage() . "\n";
            }
        } else {
            $query = 'insert into newsletter_subscriber '
                    . '(store_id, change_status_at, customer_id, subscriber_email, subscriber_status, subscriber_confirm_code) VALUES '
                    . '(?,?,?,?,?,?)';
            try {
                $write->query(
                    $query,
                    [
                    $customer->getStoreId(),
                    date('Y-m-d H:i:s'),
                    $customer->getId(),
                    $customer->getEmail(),
                    \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED,
                    $this->subscriberFactory->create()->randomSequence()
                    ]
                );
            } catch (\Exception $ex) {
                echo $ex->getMessage() . "\n";
            }
        }
    }

    private function isSubscribed($customerId)
    {
        $result = false;
        $write = $this->resource->getConnection();
        $lines = $write->fetchAll("SELECT * FROM newsletter_subscriber WHERE customer_id=?", [$customerId]);
        foreach ($lines as $line) {
            if ($line["subscriber_status"] == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
                $result = true;
            }
        }
        return $result;
    }

    private function syncWithCleverreach($customerId)
    {
        $this->initializer->registerServices();
        $this->enqueueTask(new RecipientSyncTask([RecipientService::CUSTOMER_ID_PREFIX . $customerId]));
    }

    /**
     * @param Task $task
     */
    protected function enqueueTask(Task $task)
    {
        if (!$this->isInitialSyncTaskEnqueued()) {
            return;
        }

        try {
            /**
 * @var Queue $queueService
*/
            $queueService = ServiceRegister::getService(Queue::CLASS_NAME);
            $queueService->enqueue($this->getConfigService()->getQueueName(), $task);
        } catch (QueueStorageUnavailableException $ex) {
            Logger::logDebug(
                json_encode(
                    [
                        'Message' => 'Failed to enqueue task ' . $task->getType(),
                        'ExceptionMessage' => $ex->getMessage(),
                        'ExceptionTrace' => $ex->getTraceAsString(),
                        'ShopData' => Serializer::serialize($task)
                        ]
                ),
                'Integration'
            );
        }
    }

    /**
     * @param string $tag
     * @param string $type
     *
     * @return TagCollection
     */
    protected function formatTagForDelete($tag, $type)
    {
        return new TagCollection([new Tag($tag, $type)]);
    }

    /**
     * @return ConfigService
     */
    protected function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }

    /**
     * Returns whether initial sync task in enqueued.
     *
     * @return bool
     */
    private function isInitialSyncTaskEnqueued()
    {
        /**
 * @var Queue $queueService
*/
        $queueService = ServiceRegister::getService(Queue::CLASS_NAME);
        $initialSyncTask = $queueService->findLatestByType('InitialSyncTask');

        return $initialSyncTask !== null;
    }
}

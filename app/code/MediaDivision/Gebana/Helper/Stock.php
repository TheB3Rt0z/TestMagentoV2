<?php

namespace MediaDivision\Gebana\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Catalog\Model\ProductFactory;
use \Magento\CatalogInventory\Api\StockRegistryInterface;
use \Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use \Magento\Inventory\Model\ResourceModel\Stock\CollectionFactory;
use \Magento\InventorySales\Model\ResourceModel\GetAssignedStockIdForWebsite;
use \Magento\ProductAlert\Block\Product\View;
use Amasty\Xnotif\Helper\Data;
use Rocktechnolab\Catalogprice\Helper\Data as RockTechnolabHelper;
use Magento\Tax\Model\TaxCalculation as TaxCalculation;
use Magento\Customer\Model\Session as Session;
use Magento\Store\Model\StoreManagerInterface as StoreManager;

class Stock extends AbstractHelper
{

    private $productFactory;
    private $stockRegistryInterface;
    private $getSalableQuantityDataBySku;
    private $stockCollectionFactory;
    private $getAssignedStockIdForWebsite;
    private $stockConfig = [];
    private $stockNameList;
    private $children;
    private $view;
    private $xnotfHelper;
    private $rockTechnologyHelper;
    private $calculation;
    private $session;
    private $storeManager;

    public function __construct(
        GetAssignedStockIdForWebsite $getAssignedStockIdForWebsite,
        CollectionFactory $stockCollectionFactory,
        ProductFactory $productFactory,
        StockRegistryInterface $stockregistryInterface,
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        View $view,
        Data $xnotfHelper,
        RockTechnolabHelper $rockTechnologyHelper,
        TaxCalculation $calculation,
        Session $session,
        StoreManager $storeManager,
        Context $context
    ) {
        $this->getAssignedStockIdForWebsite = $getAssignedStockIdForWebsite;
        $this->stockCollectionFactory = $stockCollectionFactory;
        $this->productFactory = $productFactory;
        $this->stockRegistryInterface = $stockregistryInterface;
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->view = $view;
        $this->xnotfHelper = $xnotfHelper;
        $this->rockTechnologyHelper = $rockTechnologyHelper;
        $this->calculation = $calculation;
        $this->session = $session;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getStatus($productId)
    {
        $stockNameList = $this->stockNameList ? $this->stockNameList : $this->getStockNameList();
        $result = ''; // Bestellen, Vormerken, Vorbestellen
        $product = $this->productFactory->create()->load($productId);
        $stockId = $this->getAssignedStockIdForWebsite->execute($product->getStore()->getWebsite()->getCode());
        $stockName = $stockNameList[$stockId];

        $stockItem = $this->stockRegistryInterface->getStockItem($product->getId(), $product->getStore()->getWebsiteId());

        $stockStatus = $product->isSaleable();
        $qty = 0;
        if ($product->getTypeId() == 'configurable') {
            foreach ($product->getTypeInstance()->getUsedProductIds($product) as $childId) {
                $child = $this->productFactory->create()->load($childId);
                try {
                    foreach ($this->getSalableQuantityDataBySku->execute($child->getSku()) as $sourceItem) {
                        $this->stockConfig[$childId][] = $sourceItem;
                        // Sources mit neg. Anzahl überspringen
                        if (($sourceItem["qty"] > 0) && ($sourceItem["stock_name"] == $stockName)) {
                            $qty += (int)$sourceItem["qty"];
                        }
                    }
                } catch (\Exception $ex) {
                    // do nothing
                }
            }
        } else {
            foreach ($this->getSalableQuantityDataBySku->execute($product->getSku()) as $sourceItem) {
                // Sources mit neg. Anzahl überspringen
                if (($sourceItem["qty"] > 0) && ($sourceItem["stock_name"] == $stockName)) {
                    $qty += (int)$sourceItem["qty"];
                }
            }
        }

        return $this->getButtonType($qty, $stockItem);
    }

    private function getButtonType($qty, $stockItem)
    {
    	if ($qty > 0) {
            $result = 'Bestellen';
        } elseif ($stockItem->getBackorders() == 1
            || $stockItem->getBackorders() == 2) {
            $result = 'Vorbestellen';
        } elseif ($stockItem->getBackorders() == 0) {
            $result = 'Vormerken';
        } else {
            $result = 'Unbekannt';
        }

        return $result;
    }

    private function getStockNameList()
    {
        $stockNameList = [];
        foreach ($this->stockCollectionFactory->create() as $item) {
            $stockNameList[$item->getStockId()] = $item->getName();
        }
        return $stockNameList;
    }

    public function getChildren($parentProduct)
    {
        if (!$this->children) {
            return $parentProduct->getTypeInstance()->getUsedProducts($parentProduct);
        }
        return $this->children;
    }


    public function getStockConfig($product, $config)
    {

        $productIdOptionId = $this->getProductIdOptionId($config);
        if (!is_null($productIdOptionId)) {
            $stockNameList = $this->stockNameList ? $this->stockNameList : $this->getStockNameList();

            $children = $this->children ? $this->children : $this->getChildren($product);
            $result = [];

            foreach ($children as $product) {
                $stockId = $this->getAssignedStockIdForWebsite->execute($product->getStore()->getWebsite()->getCode());
                $stockName = $stockNameList[$stockId];
                $stockItem = $this->stockRegistryInterface->getStockItem($product->getId(), $product->getStore()->getWebsiteId());

                $qty = 0;
                foreach ($this->getSalableQuantityDataBySku->execute($product->getSku()) as $sourceItem) {
                    if (($sourceItem["qty"] > 0) && ($sourceItem["stock_name"] == $stockName)) {
                        $qty += (int)$sourceItem["qty"];
                    }
                }
                $result[$product->getId()] = $this->getButtonType($qty, $stockItem);
            }
            $data = [];

            foreach ($result as $id => $buttonType) {
                if (array_key_exists($id, $productIdOptionId)) {
                    $data[$productIdOptionId[$id]] = [
                        'buttonType' => $buttonType,
                        'id' => $id
                    ];
                }
            }
            return json_encode($data);
        }
    }

    public function getProductIdOptionId($config)
    {
        $data = json_decode($config, true);
        if (isset($data['attributes']) && count($data['attributes'])) {
            $data = reset($data['attributes'])['options'];
            $result = [];
            foreach ($data as $item) {
                $result[reset($item['products'])] = $item['id'];
            }
            return $result;
        }
    }

    public function getPopUps($product, $config)
    {
        $config = json_decode($config, true);
        if (!is_null($config)) {
            $children = $this->children ? $this->children : $this->getChildren($product);
            $xnotfHelper = $this->xnotfHelper;
            $productAlert = $this->view;
            $html = '';
            foreach ($children as $child) {
                foreach ($config as $productItem) {
                    if ($child->getId() == $productItem['id'] && $productItem['buttonType'] == 'Vormerken') {
                        $html .= $xnotfHelper->observeStockAlertBlock($child, $productAlert);
                    }
                }
            }
            return $html;
        }
    }

    public function getChildrenPriceConfig($parentProduct)
    {
        $childIdList = $parentProduct->getTypeInstance()->getChildrenIds($parentProduct->getId());
        $childrenIds = reset($childIdList);
        $productCollection = $this->productFactory->create()
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['in' => $childrenIds]);

        $symbol = $this->rockTechnologyHelper->getSymbol();
        $result = [];

        $customerId = $this->session->getCustomer()->getId();
        $storeId = $this->storeManager->getStore()->getStoreId();

        if ($productCollection->count()) {
            foreach ($productCollection as $product) {
                $productTaxClassId = $product->getData('tax_class_id');
                $basePricaAmount = $product->getBasePriceAmount();
                $basePricaAmount = str_replace("x", "<span>x</span>", $basePricaAmount);

                $incl = $product->getPreisInklVersand() ? 'inkl' : 'zzgl';

                $result[$product->getId()] = [
                    'symbol' => $symbol,
                    'base_price_base_amount' => $product->getBasePriceBaseAmount(),
                    'base_price_base_unit' => $product->getAttributeText("base_price_base_unit"),
                    'base_price_unit' => $product->getAttributeText("base_price_unit"),
                    'base_price' => $product->getBasePrice(),
                    'final_price' => $product->getFinalPrice(),
                    'base_price_amount' => $basePricaAmount,
                    'rate' => $this->calculation->getCalculatedRate($productTaxClassId, $customerId, $storeId),
                    'incl' => $incl
                ];
            }
        }
        if (count($result)) {
            return json_encode($result);
        }
    }
}

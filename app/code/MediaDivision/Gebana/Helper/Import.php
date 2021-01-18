<?php

namespace MediaDivision\Gebana\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\Filesystem\DirectoryList;
use \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class Import extends AbstractHelper
{

    private $debug;
    private $productsFile = "/var/import/products.csv";
    private $installDir;
    private $categorieCollectionFactory;
    private $categoryList;
    private $visibilityList = [
        "Einzeln nicht sichtbar" => 1,
        "Katalog" => 2,
        "Suche" => 3,
        "Katalog, Suche" => 4,
    ];
    private $websites = [
        "chstore" => "chgerman",
        "reseller" => "chreseller",
        "sestore" => "eusweden",
        "eustore" => "eugerman",
    ];
    private $stores = [
        "chgerman" => "chstore",
        "chfrench" => "chstore",
        "chreseller" => "reseller",
        "eusweden" => "sestore",
        "eugerman" => "eustore",
        "euaustria" => "eustore",
    ];
    private $backorders = [
        "No Backorders" => 0,
        "allow Qty Below 0" => 1,
        "Allow Qty Below 0 and Notify Customer	" => 2,
    ];
    private $skuList = [];

    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        Context $context,
        DirectoryList $directoryList
    ) {
        $this->categorieCollectionFactory = $categoryCollectionFactory;
        $this->installDir = $directoryList->getRoot();
        parent::__construct($context);
    }

    private function getWebsiteList()
    {
        $websiteList = [];
        $bom = pack('H*', 'EFBBBF');
        if (($handle = fopen($this->installDir . $this->productsFile, "r")) !== false) {
            $head = fgetcsv($handle, 0, ";");
            $head[0] = preg_replace("/^$bom/", '', $head[0]); // BOM entfernen

            while (($line = fgetcsv($handle, 0, ";")) !== false) {
                $data = $this->getData($line, $head);
                if (!isset($websiteList[$data["sku"]]) || !in_array($data["product_websites"], $websiteList[$data["sku"]])) {
                    $websiteList[$data["sku"]][] = $data["product_websites"];
                }
            }
        }
        return $websiteList;
    }

    public function importProducts($debug)
    {
        $websiteList = $this->getWebsiteList();
        $this->debug = $debug;
        $bom = pack('H*', 'EFBBBF');
        $csvData = [];
        $configurable = [];
        $type = "";
        $taxClass = "";
        $priceForGroupPrices = "";
        $hasPriceScale = "";
        $category = "";
        $backorders = "";
        $this->createCategoryList();
        if (($handle = fopen($this->installDir . $this->productsFile, "r")) !== false) {
            $head = fgetcsv($handle, 0, ";");
            $head[0] = preg_replace("/^$bom/", '', $head[0]); // BOM entfernen

            while (($line = fgetcsv($handle, 0, ";")) !== false) {
                $data = $this->getData($line, $head);
                // Manche Zeilen haben keinen Type-Wert. In dem Fall zählt die zuletzt gemachte Definition der vorherigen Zeilen.
                if ($data["type"] && ($data["type"] != $type)) {
                    $type = $data["type"];
                }
                $data["type"] = $type;

                if ($data["tax_class_id"] && ($data["tax_class_id"] != $taxClass)) {
                    $taxClass = $data["tax_class_id"];
                }
                $data["tax_class_id"] = $taxClass;

                if ($data["has_price_scale"] && ($data["has_price_scale"] != $hasPriceScale)) {
                    $hasPriceScale = $data["has_price_scale"];
                }
                $data["has_price_scale"] = $hasPriceScale;

                if ($data["category"] && ($data["category"] != $category)) {
                    $category = $data["category"];
                }
                $data["category"] = $category;

                if (($data["backorders"] != "") && ($data["backorders"] != $backorders)) {
                    $backorders = $data["backorders"];
                }
                $data["backorders"] = $backorders;

                if ($data["price"]) {
                    $data["price"] = preg_replace('/,/', '.', $data["price"]);
                    $priceForGroupPrices = $data["price"];
                }
                $data["price_for_group_prices"] = $priceForGroupPrices;
                if ($type == "simple") {
                    $csvData[] = $this->createProduct($data, $websiteList);
                } else {
                    $configurable[] = $data;
                }
            }
            fclose($handle);
        }

        $simplesSku = "";
        $configurableAttributes = "";
        foreach ($configurable as $data) {
            if ($data["simples_skus"] && ($data["simples_skus"] != $simplesSku)) {
                $simplesSku = $data["simples_skus"];
            }
            $data["simples_skus"] = $simplesSku;
            if ($data["configurable_attributes"] && ($data["configurable_attributes"] != $configurableAttributes)) {
                $configurableAttributes = $data["configurable_attributes"];
            }
            $data["configurable_attributes"] = $configurableAttributes;
            $csvData[] = $this->createProduct($data, $websiteList);
        }

        return $csvData;
    }

    private function getCategoryId($categoryName)
    {
        if (!$categoryName) {
            return "__MAGMI_IGNORE__";
        }
        $categoryId = 3; // Kategorie: Produkte
        if (isset($this->categoryList[mb_strtoupper($categoryName)])) {
            $categoryId = $this->categoryList[mb_strtoupper($categoryName)];
        }
        return $categoryId;
    }

    private function getData($line, $head)
    {
        $data = [];
        foreach ($line as $index => $value) {
            $data[$head[$index]] = $value;
        }
        return $data;
    }

    private function createPictureList($sku)
    {
        $list = [];
        foreach (glob($this->installDir . "/pub/media/import/" . $sku . "_*") as $image) {
            $list[] = $image;
        }
        return $list;
    }

    private function createCategoryList()
    {
        foreach ($this->categorieCollectionFactory->create()->addAttributeToSelect('name') as $category) {
            $this->categoryList[mb_strtoupper($category->getName())] = $category->getId();
        }
    }

    private function createProduct($data, $websiteList)
    {
        $pictureList = $this->createPictureList($data["sku"]);
        $categoryIds = $this->getCategoryId($data["category"]);
        $qty = isset($data["qty"]) && $data["qty"] ? $data["qty"] : 0;
        $store = $data["store"];
        if (!$store) {
            if (isset($this->websites[$data["product_websites"]])) {
                $store = $this->websites[$data["product_websites"]];
            } else {
                $store = "__MAGMI_IGNORE__";
            }
        }
        if (!in_array($data["sku"], $this->skuList)) {
            $this->skuList[] = $data["sku"];
            if ($store == "__MAGMI_IGNORE__") {
                $store = "admin";
            } else {
                $store .= ",admin";
            }
        }
        $website = $data["product_websites"];
        if (!$website) {
            if (isset($this->stores[$data["store"]])) {
                $website = $this->stores[$data["store"]];
            } else {
                $website = "__MAGMI_IGNORE__";
            }
        }

        $status = "__MAGMI_IGNORE__";
        if ($data["status"]) {
            $status = $data["status"] == 'aktiv' ? 1 : 2;
        }
        $nbfm = "__MAGMI_IGNORE__";
        if ($data["nicht_berechtigt_fur_mengenrab"]) {
            $nbfm = $data["nicht_berechtigt_fur_mengenrab"] == "ja" ? 1 : 0;
        }
        $piv = "__MAGMI_IGNORE__";
        if ($data["preis_inkl_versand"]) {
            $piv = $data["preis_inkl_versand"] == 'ja' ? 1 : 0;
        }
        $product = [
            "sku" => $data["sku"],
            "websites" => implode(',', $websiteList[$data["sku"]]),
            "store" => $store,
            "name" => $data["name"] ? $data["name"] : "__MAGMI_IGNORE__",
            "short_description" => $data["short_description"] ? $data["short_description"] : "__MAGMI_IGNORE__",
            "description" => $data["description"] ? $data["description"] : "__MAGMI_IGNORE__",
            "price" => $data["price_for_group_prices"] ? $data["price_for_group_prices"] : "__MAGMI_IGNORE__",
            "type" => $data["type"],
            "simples_skus" => preg_replace('/, */', ',', $data["simples_skus"]),
            "configurable_attributes" => $data["configurable_attributes"],
            "tax_class_id" => $data["tax_class_id"] ? $data["tax_class_id"] : "__MAGMI_IGNORE__",
            "category_ids" => $categoryIds,
            "visibility" => isset($this->visibilityList[$data["visibility"]]) ? $this->visibilityList[$data["visibility"]] : "__MAGMI_IGNORE__",
            "attribute_set" => "Default", //$data["attribute_set"] ? $data["attribute_set"] : "Default",
            "status" => $status,
            "image" => isset($pictureList[0]) ? $pictureList[0] : "__MAGMI_IGNORE__",
            "thumbnail" => isset($pictureList[0]) ? $pictureList[0] : "__MAGMI_IGNORE__",
            "small_image" => isset($pictureList[0]) ? $pictureList[0] : "__MAGMI_IGNORE__",
            "compiere_product_id" => $data["compiere_product_id"] ? $data["compiere_product_id"] : "__MAGMI_IGNORE__",
            "interner_artikelname" => $data["interner_artikelname"] ? $data["interner_artikelname"] : "__MAGMI_IGNORE__",
            "long_product_name" => $data["long_product_name"] ? $data["long_product_name"] : "__MAGMI_IGNORE__",
            "wieviel_ist_das" => $data["wieviel_ist_das"] ? $data["wieviel_ist_das"] : "__MAGMI_IGNORE__",
            "aufbewahrung_und_haltbarkeit" => $data["aufbewahrung_und_haltbarkeit"] ? $data["aufbewahrung_und_haltbarkeit"] : "__MAGMI_IGNORE__",
            "wann_ist_saison" => $data["wann_ist_saison"] ? $data["wann_ist_saison"] : "__MAGMI_IGNORE__",
            "verwendung_und_zubereitung" => $data["verwendung_und_zubereitung"] ? $data["verwendung_und_zubereitung"] : "__MAGMI_IGNORE__",
            "nachhaltigkeit_und_transparenz" => $data["nachhaltigkeit_und_transparenz"] ? $data["nachhaltigkeit_und_transparenz"] : "__MAGMI_IGNORE__",
            "addedvalue" => $data["addedvalue"] ? $data["addedvalue"] : "__MAGMI_IGNORE__",
            "base_price" => $data["base_price"] ? $data["base_price"] : "__MAGMI_IGNORE__",
            "base_price_amount" => $data["base_price_amount"] ? $data["base_price_amount"] : "__MAGMI_IGNORE__",
            "base_price_unit" => $data["base_price_unit"] ? $data["base_price_unit"] : "__MAGMI_IGNORE__",
            "base_price_base_amount" => $data["base_price_base_amount"] ? $data["base_price_base_amount"] : "__MAGMI_IGNORE__",
            "base_price_base_unit" => $data["base_price_base_unit"] ? $data["base_price_base_unit"] : "__MAGMI_IGNORE__",
            "menge" => $data["menge"] ? $data["menge"] : "__MAGMI_IGNORE__",
            "country_of_manufacture" => $data["country_of_manufacture"] ? $data["country_of_manufacture"] : "__MAGMI_IGNORE__",
            "origin" => $data["origin"] ? $data["origin"] : "__MAGMI_IGNORE__",
            "delivery_date" => $data["delivery_date"] ? $data["delivery_date"] : "__MAGMI_IGNORE__",
            "brennwert_energie" => $data["brennwert_energie"] ? $data["brennwert_energie"] : "__MAGMI_IGNORE__",
            "eiweiss_proteines" => $data["eiweiss_proteines"] ? $data["eiweiss_proteines"] : "__MAGMI_IGNORE__",
            "gesaettigte_fettsaeuren" => $data["gesaettigte_fettsaeuren"] ? $data["gesaettigte_fettsaeuren"] : "__MAGMI_IGNORE__",
            "fett_matieres_grasses" => $data["fett_matieres_grasses"] ? $data["fett_matieres_grasses"] : "__MAGMI_IGNORE__",
            "kohlenhydrate_glucides" => $data["kohlenhydrate_glucides"] ? $data["kohlenhydrate_glucides"] : "__MAGMI_IGNORE__",
            "salz_sel" => $data["salz_sel"] ? $data["salz_sel"] : "__MAGMI_IGNORE__",
            "zucker_sucres" => $data["zucker_sucres"] ? $data["zucker_sucres"] : "__MAGMI_IGNORE__",
            "qty" => 10, //$qty,
            "is_in_stock" => $qty <= 0 ? 0 : 1,
            "nicht_berechtigt_fur_mengenrab" => $nbfm,
            "preis_inkl_versand" => $piv,
            "backorders" => $data["backorders"] ? $this->backorders[$data["backorders"]] : "__MAGMI_IGNORE__",
            "use_config_backorders" => 0,
            "inhalt_und_naehrwertangaben" => $data["inhalt_und_naehrwertangaben"] ? $data["inhalt_und_naehrwertangaben"] : "__MAGMI_IGNORE__",
                //"url_key" => $data["url_key"] ? $data["url_key"] : "__MAGMI_IGNORE__",
                //"url_path" => $data["url_path"] ? $data["url_path"] : "__MAGMI_IGNORE__",
                //"delivery_time" => $data["delivery_time"] ? $data["delivery_time"] : "__MAGMI_IGNORE__",
                //"min_qty" => $data["min_qty"] ? $data["min_qty"] : "__MAGMI_IGNORE__",
                //"use_config_min_qty" => $data["use_config_min_qty"] ? $data["use_config_min_qty"] : "__MAGMI_IGNORE__",
                //"is_qty_decimal" => $data["is_qty_decimal"] ? $data["is_qty_decimal"] : "__MAGMI_IGNORE__",
                //"min_sale_qty" => $data["min_sale_qty"] ? $data["min_sale_qty"] : "__MAGMI_IGNORE__",
                //"use_config_min_sale_qty" => $data["use_config_min_sale_qty"] ? $data["use_config_min_sale_qty"] : "__MAGMI_IGNORE__",
                //"max_sale_qty" => $data["max_sale_qty"] ? $data["max_sale_qty"] : "__MAGMI_IGNORE__",
                //"use_config_max_sale_qty" => $data["use_config_max_sale_qty"] ? $data["use_config_max_sale_qty"] : "__MAGMI_IGNORE__",
                //"notify_stock_qty" => $data["notify_stock_qty"] ? $data["notify_stock_qty"] : "__MAGMI_IGNORE__",
                //"use_config_notify_stock_qty" => $data["use_config_notify_stock_qty"] ? $data["use_config_notify_stock_qty"] : "__MAGMI_IGNORE__",
                //"manage_stock" => $data["manage_stock"] ? $data["manage_stock"] : "__MAGMI_IGNORE__",
                //"use_config_manage_stock" => $data["use_config_manage_stock"] ? $data["use_config_manage_stock"] : "__MAGMI_IGNORE__",
                //"stock_status_changed_auto" => $data["stock_status_changed_auto"] ? $data["stock_status_changed_auto"] : "__MAGMI_IGNORE__",
                //"use_config_qty_increments" => $data["use_config_qty_increments"] ? $data["use_config_qty_increments"] : "__MAGMI_IGNORE__",
                //"qty_increments" => $data["qty_increments"] ? $data["qty_increments"] : "__MAGMI_IGNORE__",
                //"use_config_enable_qty_inc" => $data["use_config_enable_qty_inc"] ? $data["use_config_enable_qty_inc"] : "__MAGMI_IGNORE__",
                //"enable_qty_increments" => $data["enable_qty_increments"] ? $data["enable_qty_increments"] : "__MAGMI_IGNORE__",
                //"is_decimal_divided" => $data["is_decimal_divided"] ? $data["is_decimal_divided"] : "__MAGMI_IGNORE__",
        ];
        if ($data["type"] == "configurable") {
            $product["is_in_stock"] = 1; // konfigurierbare Produkte müssen immer 'auf Lager' als Wert haben.
        }
        $product = $this->calculateGroupPrices($product, $data);
        return $product;
    }

    private function calculateGroupPrices($product, $data)
    {
        if (($data["has_price_scale"] == "ja") && ($data["price_for_group_prices"] > 0) && (in_array($product["store"], ["chreseller"]))) {
            // Reseller
            $product["tier_price:WVK / Partizipant CH - 1%"] = '-25%'; //$data["price_for_group_prices"] * 0.75;
            $product["tier_price:WVK / Partizipant CH - 10%"] = '-25%'; //$data["price_for_group_prices"] * 0.75;
            $product["tier_price:WVK / Partizipant CH - 2%"] = '-25%'; //$data["price_for_group_prices"] * 0.75;
            $product["tier_price:Shop-in-Shop-Partner CH"] = '-35%'; //$data["price_for_group_prices"] * 0.65;
            $product["tier_price:Wiederverkäufer CH"] = '-25%'; //$data["price_for_group_prices"] * 0.75;
            $product["tier_price:Hofläden EU"] = "__MAGMI_IGNORE__";
            $product["tier_price:Wiederverkäufer EU"] = "__MAGMI_IGNORE__";
        } elseif (($data["has_price_scale"] == "ja") && ($data["price_for_group_prices"] > 0) && (in_array($product["store"], ["eugerman", "euaustria"]))) {
            // EU-Shop
            $product["tier_price:WVK / Partizipant CH - 1%"] = "__MAGMI_IGNORE__";
            $product["tier_price:WVK / Partizipant CH - 10%"] = "__MAGMI_IGNORE__";
            $product["tier_price:WVK / Partizipant CH - 2%"] = "__MAGMI_IGNORE__";
            $product["tier_price:Shop-in-Shop-Partner CH"] = "__MAGMI_IGNORE__";
            $product["tier_price:Wiederverkäufer CH"] = "__MAGMI_IGNORE__";
            $product["tier_price:Hofläden EU"] = '-40%'; //$data["price_for_group_prices"] * 0.6;
            $product["tier_price:Wiederverkäufer EU"] = '-25%'; //$data["price_for_group_prices"] * 0.75;
        } else {
            // Sonstiges
            $product["tier_price:WVK / Partizipant CH - 1%"] = "__MAGMI_IGNORE__";
            $product["tier_price:WVK / Partizipant CH - 10%"] = "__MAGMI_IGNORE__";
            $product["tier_price:WVK / Partizipant CH - 2%"] = "__MAGMI_IGNORE__";
            $product["tier_price:Shop-in-Shop-Partner CH"] = "__MAGMI_IGNORE__";
            $product["tier_price:Wiederverkäufer CH"] = "__MAGMI_IGNORE__";
            $product["tier_price:Hofläden EU"] = "__MAGMI_IGNORE__";
            $product["tier_price:Wiederverkäufer EU"] = "__MAGMI_IGNORE__";
        }
        return $product;
    }
}

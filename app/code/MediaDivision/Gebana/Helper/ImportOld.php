<?php

namespace MediaDivision\Gebana\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Framework\Filesystem\DirectoryList;

class ImportOld extends AbstractHelper
{

    private $debug;
    private $activeProductsFile = "/var/import/Aktive_Produkte.csv";
    private $allProductsFile = "/var/import/catalog_product.csv";
    private $installDir;

    public function __construct(Context $context, DirectoryList $directoryList)
    {
        $this->installDir = $directoryList->getRoot();
        parent::__construct($context);
    }

    public function importOldProducts($debug)
    {
        $this->debug = $debug;
        $csvData = [];

        if (($handle = fopen($this->installDir . $this->allProductsFile, "r")) !== false) {
            $rawData = [];
            $head = fgetcsv($handle, 0, ","); // Headerzeile
            $sku = '';
            while (($line = fgetcsv($handle, 0, ",")) !== false) {
                $data = [];
                foreach ($line as $index => $item) {
                    $data[$head[$index]] = $item;
                }
                if ($data["sku"]) {
                    $sku = $data["sku"];
                }
                $rawData[$sku][] = $data;
            }
            fclose($handle);
        }

        if (($handle = fopen($this->installDir . $this->activeProductsFile, "r")) !== false) {
            fgetcsv($handle, 0, ";"); // Headerzeile wegwerfen
            while (($line = fgetcsv($handle, 0, ";")) !== false) {
                $sku = trim($line[1]);
                $product = $this->getTemplate();
                $product["store"] = "admin";
                $product["category_ids"] = "3";
                $product["name"] = $line[2];
                $product["sku"] = $sku;
                $product["qty"] = 100;
                $product["is_in_stock"] = 1;
                $product["price"] = 10;

                if (isset($rawData[$sku])) {
                    $rd = array_shift($rawData[$sku]);
                    $product["name"] = $rd["name"];
                    $product["description"] = $rd["description"];
                    $product["short_description"] = $rd["short_description"];
                    $product["qty"] = $rd["qty"];
                    $product["price"] = $rd["price"];
                    $product["addedvalue"] = $rd["addedvalue"];
                    $product["available_in"] = $rd["available_in"];
                    $product["brennwert_energie"] = $rd["brennwert_energie"];
                    $product["color"] = $rd["color"];
                    $product["compiere_product_id"] = $rd["compiere_product_id"];
                    $product["cost"] = $rd["cost"];
                    //$product["delivery_date"] = $rd["delivery_date"];
                    $product["delivery_time"] = $rd["delivery_time"];
                    $product["eiweiss_proteines"] = $rd["eiweiss_proteines"];
                    $product["fett_matieres_grasses"] = $rd["fett_matieres_grasses"];
                    $product["generate_meta"] = $rd["generate_meta"];
                    $product["gesaettigte_fettsaeuren"] = $rd["gesaettigte_fettsaeuren_gras_satures"];
                    $product["item_note"] = $rd["item_note"];
                    $product["kohlenhydrate_glucides"] = $rd["kohlenhydrate_glucides"];
                    $product["long_product_name"] = $rd["long_product_name"];
                    $product["manufacturer"] = $rd["manufacturer"];
                    $product["menge"] = $rd["menge"];
                    $product["not_eligible_for_vol_discounts"] = $rd["not_eligible_for_vol_discounts"];
                    $product["nutrient_attribute"] = $rd["nutrient_attribute"];
                    $product["nutrient_content"] = $rd["nutrient_content"];
                    $product["origin"] = $rd["origin"];
                    $product["product_specification"] = $rd["product_specification"];
                    $product["salz_sel"] = $rd["salz_sel"];
                    $product["shipping_addon"] = $rd["shipping_addon"];
                    $product["shipping_is_percent"] = $rd["shipping_is_percent"];
                    $product["shipping_price"] = $rd["shipping_price"];
                    $product["traceroute_id"] = $rd["traceroute_id"];
                    //$product["weight"] = $rd["weight"];
                    $product["zucker_sucres"] = $rd["zucker_sucres"];
                }
                $csvData[] = $product;
                if (isset($rawData[$sku])) {
                    foreach ($rawData[$sku] as $item) {
                        if (!$item["_store"]) {
                            continue;
                        }
                        $translated = false;
                        $productLanguage = $this->getTemplate();
                        if ($item["name"]) {
                            $productLanguage["name"] = $item["name"];
                            $translated = true;
                        }
                        if ($item["description"]) {
                            $productLanguage["description"] = $item["description"];
                            $translated = true;
                        }
                        if ($item["short_description"]) {
                            $productLanguage["short_description"] = $item["short_description"];
                            $translated = true;
                        }
                        if ($translated) {
                            $productLanguage["sku"] = $sku;
                            $productLanguage["store"] = $item["_store"];
                            $csvData[] = $productLanguage;
                        }
                    }
                }
            }
            fclose($handle);
        }

        return $csvData;
    }

    private function getTemplate()
    {
        return [
            "store" => "__MAGMI_IGNORE__",
            "category_ids" => "__MAGMI_IGNORE__",
            "sku" => "__MAGMI_IGNORE__",
            "qty" => "__MAGMI_IGNORE__",
            "is_in_stock" => "__MAGMI_IGNORE__",
            "name" => "__MAGMI_IGNORE__",
            "description" => "__MAGMI_IGNORE__",
            "short_description" => "__MAGMI_IGNORE__",
            "price" => "__MAGMI_IGNORE__",
            "addedvalue" => "__MAGMI_IGNORE__",
            "available_in" => "__MAGMI_IGNORE__",
            "brennwert_energie" => "__MAGMI_IGNORE__",
            "color" => "__MAGMI_IGNORE__",
            "compiere_product_id" => "",
            "cost" => "__MAGMI_IGNORE__",
            //"delivery_date" => "__MAGMI_IGNORE__",
            "delivery_time" => "__MAGMI_IGNORE__",
            "eiweiss_proteines" => "__MAGMI_IGNORE__",
            "fett_matieres_grasses" => "__MAGMI_IGNORE__",
            "generate_meta" => "__MAGMI_IGNORE__",
            "gesaettigte_fettsaeuren" => "__MAGMI_IGNORE__",
            "item_note" => "__MAGMI_IGNORE__",
            "kohlenhydrate_glucides" => "__MAGMI_IGNORE__",
            "long_product_name" => "__MAGMI_IGNORE__",
            "manufacturer" => "__MAGMI_IGNORE__",
            "menge" => "__MAGMI_IGNORE__",
            "not_eligible_for_vol_discounts" => "__MAGMI_IGNORE__",
            "nutrient_attribute" => "__MAGMI_IGNORE__",
            "nutrient_content" => "__MAGMI_IGNORE__",
            "origin" => "__MAGMI_IGNORE__",
            "product_specification" => "__MAGMI_IGNORE__",
            "salz_sel" => "__MAGMI_IGNORE__",
            "shipping_addon" => "__MAGMI_IGNORE__",
            "shipping_is_percent" => "__MAGMI_IGNORE__",
            "shipping_price" => "__MAGMI_IGNORE__",
            "traceroute_id" => "__MAGMI_IGNORE__",
            //"weight" => "__MAGMI_IGNORE__",
            "zucker_sucres" => "__MAGMI_IGNORE__",
        ];
    }
}

//Attributcode
//addedvalue
//available_in
//brennwert_energie
//color
//compiere_product_id
//cost
//delivery_date
//delivery_time
//eiweiss_proteines
//fett_matieres_grasses
//generate_meta
//gesaettigte_fettsaeuren
//item_note
//kohlenhydrate_glucides
//long_product_name
//manufacturer
//menge
//not_eligible_for_vol_discounts
//nutrient_attribute
//nutrient_content
//origin
//product_specification
//salz_sel
//shipping_addon
//shipping_is_percent
//shipping_price
//traceroute_id
//weight
//zucker_sucres
//

//    [sku] => 15.401.01
//    [_store] =>
//    [_attribute_set] => Default
//    [_type] => simple
//    [_category] =>
//    [_root_category] =>
//    [_product_websites] =>
//    [name] =>
//    [description] =>
//    [short_description] =>
//    [price] =>
//    [special_price] =>
//    [special_from_date] =>
//    [special_to_date] =>
//    [cost] =>
//    [manufacturer] =>
//    [meta_title] =>
//    [meta_keyword] =>
//    [meta_description] =>
//    [image] =>
//    [small_image] =>
//    [thumbnail] =>
//    [media_gallery] =>
//    [color] =>
//    [news_from_date] =>
//    [news_to_date] =>
//    [gallery] =>
//    [status] =>
//    [tax_class_id] =>
//    [url_key] =>
//    [url_path] =>
//    [minimal_price] =>
//    [visibility] =>
//    [custom_design] =>
//    [custom_design_from] =>
//    [custom_design_to] =>
//    [custom_layout_update] =>
//    [page_layout] =>
//    [options_container] =>
//    [required_options] => 0
//    [has_options] => 0
//    [image_label] =>
//    [small_image_label] =>
//    [thumbnail_label] =>
//    [created_at] => 2017-10-04 13:50:10
//    [updated_at] => 2017-10-04 13:50:10
//    [gift_message_available] =>
//    [delivery_time] => 1 - 2 Wochen
//    [nutrient_attribute] =>
//    [product_specification] =>
//    [traceroute_id] =>
//    [nutrient_content] =>
//    [compiere_product_id] =>
//    [origin] =>
//    [addedvalue] =>
//    [base_price_amount] =>
//    [base_price_unit] =>
//    [base_price_base_amount] =>
//    [base_price_base_unit] =>
//    [project_id] =>
//    [partner_id] =>
//    [traceability_id] =>
//    [generate_meta] => Nein
//    [shipping_price] =>
//    [shipping_is_percent] =>
//    [shipping_addon] =>
//    [available_in] =>
//    [menge] =>
//    [brennwert_energie] =>
//    [fett_matieres_grasses] =>
//    [gesaettigte_fettsaeuren_gras_satures] =>
//    [kohlenhydrate_glucides] =>
//    [zucker_sucres] =>
//    [eiweiss_proteines] =>
//    [salz_sel] =>
//    [is_imported] =>
//    [country_of_manufacture] =>
//    [msrp_enabled] =>
//    [msrp_display_actual_price_type] =>
//    [msrp] =>
//    [long_product_name] =>
//    [payment_term] =>
//    [item_note] =>
//    [not_eligible_for_vol_discounts] =>
//    [test_marzella] =>
//    [qty] => 0.0000
//    [min_qty] => 0.0000
//    [use_config_min_qty] => 1
//    [is_qty_decimal] => 0
//    [backorders] => 0
//    [use_config_backorders] => 1
//    [min_sale_qty] => 1.0000
//    [use_config_min_sale_qty] => 1
//    [max_sale_qty] => 0.0000
//    [use_config_max_sale_qty] => 1
//    [is_in_stock] => 0
//    [notify_stock_qty] =>
//    [use_config_notify_stock_qty] => 1
//    [manage_stock] => 0
//    [use_config_manage_stock] => 1
//    [stock_status_changed_auto] => 1
//    [use_config_qty_increments] => 1
//    [qty_increments] => 0.0000
//    [use_config_enable_qty_inc] => 1
//    [enable_qty_increments] => 0
//    [is_decimal_divided] => 0
//    [_links_related_sku] =>
//    [_links_related_position] =>
//    [_links_crosssell_sku] =>
//    [_links_crosssell_position] =>
//    [_links_upsell_sku] =>
//    [_links_upsell_position] =>
//    [_associated_sku] =>
//    [_associated_default_qty] =>
//    [_associated_position] =>
//    [_tier_price_website] =>
//    [_tier_price_customer_group] =>
//    [_tier_price_qty] =>
//    [_tier_price_price] =>
//    [_group_price_website] =>
//    [_group_price_customer_group] =>
//    [_group_price_price] =>
//    [_media_attribute_id] =>
//    [_media_image] =>
//    [_media_lable] =>
//    [_media_position] =>
//    [_media_is_disabled] =>
//    [_custom_option_store] =>
//    [_custom_option_type] =>
//    [_custom_option_title] =>
//    [_custom_option_is_required] =>
//    [_custom_option_price] =>
//    [_custom_option_sku] =>
//    [_custom_option_max_characters] =>
//    [_custom_option_sort_order] =>
//    [_custom_option_row_title] =>
//    [_custom_option_row_price] =>
//    [_custom_option_row_sku] =>
//    [_custom_option_row_sort] =>
//    [_super_products_sku] =>
//    [_super_attribute_code] =>
//    [_super_attribute_option] =>
//    [_super_attribute_price_corr] =>

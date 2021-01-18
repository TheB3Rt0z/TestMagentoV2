<?php

namespace MediaDivision\Basics\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ExportProducts extends Command
{

    const DEBUG = "debug";

    private $debug = false;
    private $productCollectionFactory;
    private $examples = ['12.124.01G','14.104.01'];
    private $columns = [
        "language",
        "status",
        "name",
        "sku",
        "price",
        "ts_dimensions_lenght",
        "ts_dimensions_width",
        "ts_dimensions_height",
        "weight",
        "action_label",
        "beyond_fair_label",
        "new_label",
        "home_page_show_product",
        "category_ids",
        "country_of_manufacture",
        "color",
        "description",
        "short_description",
        "wieviel_ist_das",
        "aufbewahrung_und_haltbarkeit",
        "wann_is_saison",
        "verwendung_und_zubereitung",
        "nachhaltigkeit_und_transparenz",
        "inhalt_und_naehrwertangaben",
        "addedvalue",
        "available_in",
        "brennwert_energie",
        "compiere_product_id",
        "delivery_date",
        "delivery_time",
        "eiweiss_proteines",
        "fett_matieres_grasses",
        "gesaettigte_fettsaeuren",
        "item_note",
        "kohlenhydrate_glucides",
        "long_product_name",
        "menge",
        "not_eligible_for_vol_discounts",
        "nutrient_attribute",
        "nutrient_content",
        "origin",
        "salz_sel",
        "shipping_addon",
        "shipping_is_percent",
        "shipping_price",
        "traceroute_id",
        "zucker_sucres",
    ];

    public function __construct(CollectionFactory $productCollectionFactory)
    {
        $this->productCollectionFactory = $productCollectionFactory;
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
        $this->setName("export:products")
            ->setDescription("Beispiel-Produkte exportieren, um eine Beispiel-CSV zu erhalten.")->setDefinition($options);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $collection = $this->productCollectionFactory->create()->addAttributeToSelect('*');

        $fp = fopen('product.csv', 'w');

        fputcsv($fp, $this->columns, ";");
        foreach ($collection as $product) {
            $line = [];
            if (!in_array($product->getSku(), $this->examples)) {
                continue;
            }
            foreach ($this->columns as $attribute) {
                if ($attribute == 'language') {
                    $line[] = 'admin';
                    continue;
                }
                $line[] = $product->getData($attribute);
            }
            fputcsv($fp, $line, ";");
        }
        fclose($fp);
    }
}

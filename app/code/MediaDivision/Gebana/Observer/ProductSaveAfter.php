<?php

namespace MediaDivision\Gebana\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Catalog\Api\CategoryLinkManagementInterface;
use \Magento\Catalog\Model\CategoryLinkRepository;

class ProductSaveAfter implements ObserverInterface
{

    private $categoryLinkRepository;
    private $categoryLinkManagement;
    private $categoryIdAction = 23;

    public function __construct(
        CategoryLinkManagementInterface $categoryLinkManagement,
        CategoryLinkRepository $categoryLinkRepository
    ) {
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->categoryLinkRepository = $categoryLinkRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {

        $product = $observer->getEvent()->getProduct();
        $categoryIds = $product->getCategoryIds();
        if ($product->getSpecialPrice()) {
            // In Rabatt-Kategorie einfügen
            $categoryIds[] = $this->categoryIdAction;
            $this->categoryLinkManagement->assignProductToCategories($product->getSku(), $categoryIds);
        } else {
            // Aus Rabatt-Kategorie entfernen
            if (in_array($this->categoryIdAction, $categoryIds)) {
                $this->categoryLinkRepository->deleteByIds($this->categoryIdAction, $product->getSku());
            }
        }
        return $this;
    }
}

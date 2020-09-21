<?php

namespace App\Service;

use App\Entity\CsvProduct;
use App\Entity\Product;

/**
 * Class ProductService
 * @package App\Service
 */
class ProductService
{
    /**
     * @param CsvProduct $csvProduct
     * @return Product
     */
    public function createProductFromCsv(CsvProduct $csvProduct): Product
    {
        $product = new Product();
        $product->setSku($csvProduct->getSku());
        $product->setDescription($csvProduct->getDescription());
        $product->setSpecialPrice((float)$csvProduct->getSpecialPrice());
        $product->setNormalPrice((float)$csvProduct->getNormalPrice());

        return $product;
    }

    /**
     * @param Product $product
     * @param CsvProduct $csvProduct
     */
    public function updateProductFromCsv(Product $product, CsvProduct $csvProduct): void
    {
        $product->setSku($csvProduct->getSku());
        $product->setDescription($csvProduct->getDescription());
        $product->setSpecialPrice((float)$csvProduct->getSpecialPrice());
        $product->setNormalPrice((float)$csvProduct->getNormalPrice());
    }

    /**
     * @param Product $product
     * @param CsvProduct $csvProduct
     * @return bool
     */
    public function isProductEqualCsv(Product $product, CsvProduct $csvProduct): bool
    {
        return $product->getDescription() == $csvProduct->getDescription()
            || $product->getSpecialPrice() == (float)$csvProduct->getSpecialPrice()
            || $product->getNormalPrice() == (float)$csvProduct->getNormalPrice();
    }
}
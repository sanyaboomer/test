<?php

namespace App\tests\Model;

use App\Model\CsvProduct;
use PHPUnit\Framework\TestCase;

class CsvProductTest extends TestCase
{
    public function testCsvProduct() {
        $csvProduct = new CsvProduct(
            "<script>alert()</script>",
            '<script>',
            '2',
            '1.01'
        );

        $this->assertEquals(
            '&lt;script&gt;alert&lpar;&rpar;&lt;&sol;script&gt;',
            $csvProduct->getSku()
        );

        $this->assertEquals('&lt;script&gt;', $csvProduct->getDescription());
        $this->assertEquals(2.00, $csvProduct->getNormalPrice());
        $this->assertEquals(1.01, $csvProduct->getSpecialPrice());
    }
}
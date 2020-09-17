<?php

namespace App\tests\Model;

use App\Model\CsvProduct;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvProductValidationTest extends WebTestCase
{
    /** @var ValidatorInterface */
    private $validator;

    private const ERROR_MESSAGES = [
        'the SKU is empty',
        'the description is empty',
        'the normal price is empty',
        'the normal price is not a number',
        'the normal price is negative',
        'the special price is not a number',
        'the special price is negative',
        'the special price is greater than or equal to normal price',
    ];

    protected function setUp(): void
    {
        self::bootKernel();
        // gets the special container that allows fetching private services
        $container = self::$container;
        $this->validator = $container->get('validator');
    }

    public function testSkuValidation() {
        $csvProduct = new CsvProduct('', 'lorem ipsum', '2', '1');

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(self::ERROR_MESSAGES[0], $errors->get(0)->getMessage());
    }

    public function testDescriptionValidation() {
        $csvProduct = new CsvProduct('lorem ipsum', '', '2', '1');

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(self::ERROR_MESSAGES[1], $errors->get(0)->getMessage());
    }

    public function testNormalPriceValidation() {
        // test normal price for empty
        $csvProduct = new CsvProduct('lorem ipsum', 'lorem ipsum', '', null);

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(self::ERROR_MESSAGES[2], $errors->get(0)->getMessage());

        // test normal price for invalid format
        $csvProduct = new CsvProduct('lorem ipsum', 'lorem ipsum', 'invalid', null);

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(self::ERROR_MESSAGES[3], $errors->get(0)->getMessage());

        // test normal price for gte 0
        $csvProduct = new CsvProduct('lorem ipsum', 'lorem ipsum', '-1', null);

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(self::ERROR_MESSAGES[4], $errors->get(0)->getMessage());
    }

    public function testSpecialPriceValidation() {
        // test special price is empty
        $csvProduct = new CsvProduct('lorem ipsum', 'lorem ipsum', '1', '');

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(0, $errors->count());

        // test special price is null
        $csvProduct = new CsvProduct('lorem ipsum', 'lorem ipsum', '1', null);

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(0, $errors->count());

        // test special price for invalid format
        $csvProduct = new CsvProduct('lorem ipsum', 'lorem ipsum', '1', 'invalid');

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(self::ERROR_MESSAGES[5], $errors->get(0)->getMessage());

        // test special price for gte 0
        $csvProduct = new CsvProduct('lorem ipsum', 'lorem ipsum', '1', '-1');

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(self::ERROR_MESSAGES[6], $errors->get(0)->getMessage());

        // test special price gt normal price
        $csvProduct = new CsvProduct('lorem ipsum', 'lorem ipsum', '1', '2');

        $errors = $this->validator->validate($csvProduct);
        $this->assertEquals(1, $errors->count());
        $this->assertEquals(self::ERROR_MESSAGES[7], $errors->get(0)->getMessage());
    }
}
<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Represent the row of data from the CSV
 *
 * Class CsvProduct
 * @package App\Model
 */
class CsvProduct
{
    /**
     * @Assert\NotBlank(message="the SKU is empty")
     * @var string
     */
    private $sku;

    /**
     * @Assert\NotBlank(message="the description is empty")
     * @var string
     */
    private $description;

    /**
     * @Assert\NotBlank(message="the normal price is empty")
     * @AppAssert\IsNumber(message="the normal price is not a number")
     * @Assert\GreaterThanOrEqual(
     *     value=0,
     *     message="the normal price is negative"
     * )
     * @var string
     */
    private $normalPrice;

    /**
     * @AppAssert\IsNumber(message="the special price is not a number")
     * @Assert\GreaterThanOrEqual(
     *     value=0,
     *     message="the special price is negative"
     * )
     * @var string|null
     */
    private $specialPrice;

    /**
     * @param string $sku
     * @param string $description
     * @param string $normalPrice
     * @param string $specialPrice
     */
    public function __construct(string $sku, string $description, string $normalPrice, ?string $specialPrice)
    {
        $this->sku = $sku;
        $this->description = $description;
        $this->normalPrice = $normalPrice;
        $this->specialPrice = $specialPrice;
    }

    /**
     * Compare normal and special prices only if there are numbers
     *
     * @param ExecutionContextInterface $context
     * @param $payload
     *
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (null === $this->specialPrice || '' === $this->specialPrice || !is_numeric($this->specialPrice)) {
            return;
        }

        if (null === $this->normalPrice || '' === $this->normalPrice || !is_numeric($this->normalPrice)) {
            return;
        }

        if($this->specialPrice >= $this->normalPrice) {
            $context->buildViolation('the special price is greater than or equal to normal price')
                ->atPath('specialPrice')
                ->addViolation();
        }
    }

    /**
     * @return string
     */
    public function getSku(): string
    {
        return htmlentities($this->sku, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return htmlentities($this->description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @return float
     */
    public function getNormalPrice(): float
    {
        return (float)$this->normalPrice;
    }

    /**
     * @return float|null
     */
    public function getSpecialPrice(): ?float
    {
        return $this->specialPrice !== null ? (float)$this->specialPrice : null;
    }
}
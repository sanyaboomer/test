<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as AppAssert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represent the row of data from the CSV
 *
 * @ORM\Entity()
 */
class CsvProduct
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @Assert\NotBlank(message="the SKU is empty")
     * @AppAssert\IsCleanText(message="the SKU contains html tags")
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $sku;

    /**
     * @Assert\NotBlank(message="the description is empty")
     * @AppAssert\IsCleanText(message="the description contains html tags")
     *
     * @ORM\Column(type="text", nullable=true)
     *
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
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     *
     * @var string
     */
    private $normalPrice;

    /**
     * @AppAssert\IsNumber(message="the special price is not a number")
     * @Assert\GreaterThanOrEqual(
     *     value=0,
     *     message="the special price is negative"
     * )
     *
     * @ORM\Column(type="string", length=10, nullable=true)
     *
     * @var string|null
     */
    private $specialPrice;

    /**
     * CsvProduct constructor.
     * @param int $id
     * @param string $sku
     * @param string $description
     * @param string $normalPrice
     * @param string|null $specialPrice
     */
    public function __construct(int $id, string $sku, string $description, string $normalPrice, ?string $specialPrice)
    {
        $this->id = $id;
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
        return $this->sku;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getNormalPrice(): string
    {
        return $this->normalPrice;
    }

    /**
     * @return string|null
     */
    public function getSpecialPrice(): ?string
    {
        return $this->specialPrice;
    }
}
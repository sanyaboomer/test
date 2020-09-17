<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=255)
     *
     * @var string
     */
    private $sku;

    /**
     * @ORM\Column(type="text")
     *
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2)
     *
     * @var float|null
     */
    private $normalPrice;

    /**
     * @ORM\Column(type="decimal", precision=5, scale=2, nullable=true)
     *
     * @var float|null
     */
    private $specialPrice;

    /**
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     */
    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return float|null
     */
    public function getNormalPrice(): ?float
    {
        return $this->normalPrice;
    }

    /**
     * @param float|null $normalPrice
     */
    public function setNormalPrice(?float $normalPrice): void
    {
        $this->normalPrice = $normalPrice;
    }

    /**
     * @return float|null
     */
    public function getSpecialPrice(): ?float
    {
        return $this->specialPrice;
    }

    /**
     * @param float|null $specialPrice
     */
    public function setSpecialPrice(?float $specialPrice): void
    {
        $this->specialPrice = $specialPrice;
    }
}

<?php

namespace App\Service;

use App\Entity\CsvProduct;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class CsvProductService
 * @package App\Service
 */
class CsvProductService
{
    /** @var EntityManagerInterface */
    private $em;

    /**
     * CsvProductService constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    /**
     * Clear the csv_product table
     */
    public function clearData(): void
    {
        $this->em->createQuery('DELETE FROM ' . CsvProduct::class)->execute();
    }
}
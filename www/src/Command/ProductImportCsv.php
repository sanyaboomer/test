<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\CsvProduct;
use App\Service\CsvProductService;
use App\Service\CsvReader;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class ProductImportCsv
 * @package App\Command
 */
class ProductImportCsv extends Command
{
    private const BATCH_SIZE = 500;

    /** @var string */
    protected static $defaultName = 'app:product:import';

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    /** @var ObjectRepository  $productRepository */
    private $productRepository;

    /** @var ObjectRepository  $csvProductRepository */
    private $csvProductRepository;

    /** @var ValidatorInterface */
    private $validator;

    /** @var CsvReader */
    private $reader;

    /** @var ProductService */
    private $productService;

    /** @var CsvProductService */
    private $csvProductService;


    /**
     * @var array
     */
    private $summaryData = [
        'count' => 0,
        'created' => [],
        'updated' => [],
        'skipped' => 0,
        'errors' => 0,
    ];

    /**
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param CsvReader $reader
     * @param ProductService $productService
     * @param CsvProductService $csvProductService
     */
    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CsvReader $reader,
        ProductService $productService,
        CsvProductService $csvProductService
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->validator = $validator;
        $this->reader = $reader;
        $this->productService = $productService;
        $this->csvProductService = $csvProductService;

        $this->productRepository = $em->getRepository(Product::class);
        $this->csvProductRepository = $em->getRepository(CsvProduct::class);
    }

    /**
     * @inheritDoc
     */
    public function configure(): void
    {
        $this
            ->setDescription(
                'Import Products from the CSV file.'
            )->addOption(
                'source',
                null,
                InputOption::VALUE_REQUIRED,
                'The path to the source csv file'
            );
    }

    /**
     * @inheritDoc
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileName = $input->getOption('source');

        if (!file_exists($fileName)) {
            throw new FileNotFoundException(sprintf('File "%s" does not exist', $fileName));
        }

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->logger->info('Import is started');
        $this->reader->readContent($fileName);

        // get products count before DB changes to calculate the amount of unchanged products
        $summaryData['count'] = $this->productRepository->count([]);
        $offset = 0;
        $rowNum = 0;

        do {
            /** @var CsvProduct[] $lines */
            $lines = $this->csvProductRepository->findBy([], [], self::BATCH_SIZE, $offset);

            foreach ($lines as $csvProduct) {
                $rowNum++;
                if ($this->hasRowErrors($csvProduct, $rowNum)) {
                    continue;
                }

                $sku = $csvProduct->getSku();

                /** @var Product $product */
                $product = $this->productRepository->find($sku);

                // create the new product if it does not exist
                if(!$product){
                    $product = $this->productService->createProductFromCsv($csvProduct);
                    $this->em->persist($product);
                    $this->summaryData['created'][] = $sku;
                    $this->logger->debug(sprintf('Row #%d: The product "%s" is created', $rowNum, $sku));
                } else {
                    $this->productService->updateProductFromCsv($product, $csvProduct);
                    $this->summaryData['updated'][] = $sku;
                    $this->logger->debug(sprintf('Row #%d: The product "%s" is updated', $rowNum, $sku));
                }
            }

            $this->em->flush();
            $this->em->clear();

            $offset += self::BATCH_SIZE;

        } while (count($lines) > 0);

        $this->csvProductService->clearData();

        $this->logSummaryData();

        return 0;
    }

    /**
     * Check if csv row contains the error and log them
     *
     * @param CsvProduct $csvProduct
     * @param int $currentRow
     * @return int
     */
    private function hasRowErrors(CsvProduct $csvProduct, int $currentRow): int
    {
        $errors = $this->validator->validate($csvProduct);

        if ($errors->count()) {
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $this->logger->error(sprintf('Row #%s: %s', $currentRow, $error->getMessage()));
            }
            return $errors->count();
        }

        if (in_array($csvProduct->getSku(), $this->summaryData['created'])
            || in_array($csvProduct->getSku(), $this->summaryData['updated']))
        {
            $this->logger->error(
                sprintf(
                    'Row #%s: contains duplicate for the product "%s"',
                    $currentRow,
                    $csvProduct->getSku()
                )
            );

            return 1;
        }

        return 0;
    }

    /**
     * Logging the summary data
     */
    private function logSummaryData(): void
    {
        // the table is empty from the beginning so $currentCountOfProducts - 0
        // check the difference to prevent showing the negative number
        $skipped = $this->summaryData['count'] - count($this->summaryData['updated']);
        $skipped = $skipped > 0 ? $skipped : 0;

        $this->logger->info('Import is finished');

        if ($this->summaryData['errors']) {
            $this->logger->info(sprintf('%d rows with errors', $this->summaryData['errors']));
        }

        if ($count = count($this->summaryData['created'])) {
            $this->logger->info(sprintf('%d products created', $count));
        }

        if ($count = count($this->summaryData['updated'])) {
            $this->logger->info(sprintf('%d products updated', $count));
        }

        if ($skipped) {
            $this->logger->info(sprintf('%d products skipped', $skipped));
        }
    }
}
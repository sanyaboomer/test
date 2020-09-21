<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\CsvProduct;
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

    /** @var int  */
    private $summaryDataCreated = 0;

    /** @var int  */
    private $summaryDataUpdated = 0;

    /** @var int  */
    private $summaryDataSkipped = 0;

    /** @var int  */
    private $summaryDataErrors = 0;

    /** @var int  */
    private $currentRowNumber = 0;

    /**
     * SKU of successfully created or updated products
     * @var string[]
     */
    private $handledSku = [];

    /**
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $em
     * @param ValidatorInterface $validator
     * @param CsvReader $reader
     * @param ProductService $productService
     */
    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CsvReader $reader,
        ProductService $productService
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->validator = $validator;
        $this->reader = $reader;
        $this->productService = $productService;

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
        $this->preImportData($input);
        $offset = 0;

        /** @var CsvProduct[] $lines */
        while($lines = $this->csvProductRepository->findBy([], [], self::BATCH_SIZE, $offset)) {
            foreach ($lines as $csvProduct) {
                $this->currentRowNumber++;
                if (!$this->isDataValid($csvProduct)) {
                    continue;
                }

               $this->importData($csvProduct);
            }

            $this->em->flush();
            $this->em->clear();

            $offset += self::BATCH_SIZE;
        }

        $this->logSummary();

        return 0;
    }

    /**
     * @param InputInterface $input
     * @throws FileNotFoundException
     */
    private function preImportData(InputInterface $input): void
    {
        $fileName = $input->getOption('source');

        if (!file_exists($fileName)) {
            throw new FileNotFoundException(sprintf('File "%s" does not exist', $fileName));
        }

        $this->logger->info('Import is started');

        // Disable the sql logger to keep memory
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $this->reader->readContent($fileName);
    }

    /**
     * @param CsvProduct $csvProduct
     */
    private function importData(CsvProduct $csvProduct): void
    {
        /** @var Product $product */
        $product = $this->productRepository->find($csvProduct->getSku());

        if(null === $product){
            $product = $this->productService->createProductFromCsv($csvProduct);
            $this->em->persist($product);

            $this->summaryDataCreated++;
            $this->logger->debug(sprintf('Row #%d: The product "%s" is created',  $this->currentRowNumber, $csvProduct->getSku()));
        } elseif (!$this->productService->isProductEqualCsv($product, $csvProduct)) {
            $this->productService->updateProductFromCsv($product, $csvProduct);

            $this->summaryDataUpdated++;
            $this->logger->debug(sprintf('Row #%d: The product "%s" is updated',  $this->currentRowNumber, $csvProduct->getSku()));
        } else {
            $this->summaryDataSkipped++;
            $this->logger->debug(sprintf('Row #%d: The product "%s" is skipped',  $this->currentRowNumber, $csvProduct->getSku()));
        }

        // remember the sku to check for duplicates
        $this->handledSku[] = $csvProduct->getSku();
    }

    /**
     * Check if csv row contains the errors and log them
     *
     * @param CsvProduct $csvProduct
     * @return bool
     */
    private function isDataValid(CsvProduct $csvProduct): bool
    {
        $errors = $this->validator->validate($csvProduct);

        if ($errors->count()) {
            $msg = [];
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $msg[] = $error->getMessage();
            }

            $this->logger->error(sprintf('Row #%s: %s', $this->currentRowNumber, implode(' | ', $msg)));
            $this->summaryDataErrors += $errors->count();

            return false;
        }

        if (in_array($csvProduct->getSku(), $this->handledSku)) {
            $this->logger->error(
                sprintf(
                    'Row #%s: contains duplicate for the product "%s"',
                    $this->currentRowNumber,
                    $csvProduct->getSku()
                )
            );

            $this->summaryDataErrors++;

            return false;
        }

        return true;
    }

    /**
     * Logging the summary data
     */
    private function logSummary(): void
    {
        $this->logger->info('Import is finished');

        if ($this->summaryDataErrors) {
            $this->logger->info(sprintf('%d rows with errors', $this->summaryDataErrors));
        }

        if ($this->summaryDataCreated) {
            $this->logger->info(sprintf('%d products created', $this->summaryDataCreated));
        }

        if ($this->summaryDataUpdated) {
            $this->logger->info(sprintf('%d products updated', $this->summaryDataUpdated));
        }

        if ($this->summaryDataSkipped) {
            $this->logger->info(sprintf('%d products skipped', $this->summaryDataSkipped));
        }
    }
}
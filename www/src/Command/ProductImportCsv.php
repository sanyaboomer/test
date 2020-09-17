<?php

namespace App\Command;

use App\Entity\Product;
use App\Model\CsvProduct;
use App\Service\CsvReader;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductImportCsv extends Command
{
    /** @var string */
    protected static $defaultName = 'app:product:import';

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    /** @var ValidatorInterface */
    private $validator;

    /** @var CsvReader */
    private $reader;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        CsvReader $reader
    ) {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->validator = $validator;
        $this->reader = $reader;
    }

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

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $fileName = $input->getOption('source');

        if (!file_exists($fileName)) {
            throw new FileNotFoundException(sprintf('File "%s" does not exist', $fileName));
        }

        $this->logger->info('Import is started');
        $lines = $this->reader->getContent($fileName);

        $countCreated = [];
        $countUpdated = [];
        $countErrors = 0;

        $productRepository = $this->em->getRepository(Product::class);
        // get products count before DB changes to calculate the amount of unchanged products
        $currentCountOfProducts = $productRepository->count([]);

        foreach ($lines as $key => $data) {
            if (count($data) > 4) {
                $this->logger->error(sprintf('Row #%s: more than 4 columns', $key));
                $countErrors++;
                continue;
            }


            // create csv row model to validate and convert data
            $csvProduct = new CsvProduct(
                isset($data[0]) ? $data[0] : '',
                isset($data[1]) ? $data[1] : '',
                isset($data[2]) ? $data[2] : '',
                isset($data[3]) ? $data[3] : null
            );

            $errors = $this->validator->validate($csvProduct);

            if ($errors->count()) {
                /** @var ConstraintViolation $error */
                foreach ($errors as $error) {
                    $this->logger->error(sprintf('Row #%s: %s', $key, $error->getMessage()));
                }
                $countErrors++;
                continue;
            }

            $sku = $csvProduct->getSku();
            $isNewProduct = false;
            // create the new product if it does not exist
            if(!$product = $productRepository->find($sku)){
                $product = new Product();
                $product->setSku($sku);
                $isNewProduct = true;
                $countCreated[] = $sku;
            }

            $product->setDescription($csvProduct->getDescription());
            $product->setNormalPrice($csvProduct->getNormalPrice());
            $product->setSpecialPrice($csvProduct->getSpecialPrice());

            if ($isNewProduct) {
                $this->em->persist($product);
                $this->logger->debug(sprintf('Row #%d: The product "%s" is created', $key, $sku));
            } else {
                // check if product was not created or updated before
                if (!in_array($sku, $countCreated) && !in_array($sku, $countUpdated)) {
                    $countUpdated[] = $sku;
                }

                $this->logger->debug(sprintf('Row #%d: The product "%s" is updated', $key, $sku));
            }

            if ($key % 100 == 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }
        $this->em->flush();
        $this->em->clear();

        // the table is empty from the beginning so $currentCountOfProducts - 0
        // check the difference to prevent showing the negative number
        $skipped = $currentCountOfProducts - count($countUpdated);
        $skipped = $skipped > 0 ? $skipped : 0;

        $this->logger->info('Import is finished');
        $this->logger->info(sprintf('%d rows with errors', $countErrors));
        $this->logger->info(sprintf('%d products created', count($countCreated)));
        $this->logger->info(sprintf('%d products updated', count($countUpdated)));
        $this->logger->info(sprintf('%d products skipped', $skipped));

        return 0;
    }
}
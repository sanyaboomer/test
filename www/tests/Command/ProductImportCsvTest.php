<?php

namespace App\tests\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class ProductImportCsvTest extends WebTestCase
{
    private const SOURCE = 'tests/files/products.csv';

    protected function setUp(): void
    {
        self::bootKernel();
        // gets the special container that allows fetching private services
        $container = self::$container;
        $em = $container->get('doctrine')->getManager();

        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!$metadata) {
            return;
        }

        $tool = new SchemaTool($em);
        $tool->dropSchema($metadata);
        $tool->createSchema($metadata);
    }

    protected function tearDown(): void
    {
        self::bootKernel();
        // gets the special container that allows fetching private services
        $container = self::$container;
        $em = $container->get('doctrine')->getManager();

        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!$metadata) {
            return;
        }

        $tool = new SchemaTool($em);
        $tool->dropSchema($metadata);
    }

    public function testFileNotExist(): void
    {
        $this->expectException(FileNotFoundException::class);

        $kernel = static::createKernel();
        $application = new Application($kernel);

        // create shipping data first revision
        $command = $application->find('app:product:import');

        (new CommandTester($command))->execute([
            '--env' => 'test',
            '--source' => 'fake'
        ]);
    }

    public function testCommandExecution(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        // create shipping data first revision
        $command = $application->find('app:product:import');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            '--env' => 'test',
            '--source' => self::SOURCE,
        ]);

        /** @var EntityManagerInterface $em */
        $em = self::$container->get('doctrine');

        /** @var ObjectRepository $repository */
        $repository = $em->getRepository(Product::class);

        $this->assertEquals(0, $commandTester->getStatusCode());

        // check if 3 products are created
        $this->assertCount(3, $repository->findAll());

        // test if "javascript" product is created and converted
        $product = $repository->find('&lt;script&gt;alert&lpar;&rpar;&lt;&sol;script&gt;');
        $this->assertNotNull($product);
        $this->assertEquals(2, $product->getNormalPrice());
        $this->assertEquals(1, $product->getSpecialPrice());

        // test if "null special price" product is created
        $product = $repository->find('null special price');
        $this->assertNotNull($product);
        $this->assertEquals(2.55, $product->getNormalPrice());
        $this->assertNull($product->getSpecialPrice());

        // test if "valid" product is created and updated
        $product = $repository->find('valid');
        $this->assertNotNull($product);
        $this->assertEquals(3, $product->getNormalPrice());
        $this->assertEquals(1, $product->getSpecialPrice());
    }
}

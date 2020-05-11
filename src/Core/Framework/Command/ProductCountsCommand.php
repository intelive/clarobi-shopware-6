<?php declare(strict_types=1);

namespace Clarobi\Core\Framework\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Framework\Adapter\Console\ShopwareStyle;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class ProductCountsCommand extends Command
{
    protected static $defaultName = 'plugin-commands:generate_product_counts';

    protected $productRepository;

    protected $connection;

    public function __construct(EntityRepositoryInterface $productRepository, Connection $connection)
    {
        parent::__construct();
        $this->productRepository = $productRepository;
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $io = new ShopwareStyle($input, $output);
            $io->title('Product Counts Generator');

            $start = microtime(true);

            // Query
            $sql = 'INSERT INTO clarobi_product_counts' . ' (product_id, product_auto_increment, views, adds_to_cart, created_at)'
                . ' VALUES (:product_id, :product_auto_increment, :views, :adds_to_cart, :created_at)';

            // Param values
            $paramValues = [];
            // Params types
            $paramTypes = [
                'product_id' => Types::BINARY,
                'product_auto_increment' => Types::INTEGER,
                'views' => Types::INTEGER,
                'adds_to_cart' => Types::INTEGER,
                'created_at' => Types::DATETIME_MUTABLE
            ];
            $rowsAdded = 0;
            $date = new \DateTime();

            $products = $this->productRepository->search(new Criteria(), Context::createDefaultContext());

            /** @var ProductEntity $product */
            foreach ($products as $product) {
                $views = rand(30, 1000);
                $adds = rand(70, 500);
                $paramValues = [
                    'product_id' => Uuid::fromHexToBytes($product->getId()),
                    'product_auto_increment' => $product->getAutoIncrement(),
                    'views' => $views,
                    'adds_to_cart' => $adds,
                    'created_at' => $date
                ];
                $rowsAdded += $this->connection->executeUpdate($sql, $paramValues, $paramTypes);
            }

            $time_elapsed_secs = microtime(true) - $start;

            $io->text('Rows inserted: ' . $rowsAdded . ' (time = ' . $time_elapsed_secs . 's)');
        } catch (DBALException $e) {
            $io->error($e->getMessage());
        }

        return 0;
    }
}

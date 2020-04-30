<?php declare(strict_types=1);

namespace Clarobi\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class ProductCountsHelper
 * This is called when one of the events take place and product counts table needs to be updated.
 *
 * @package Clarobi\Utils
 */
class ProductCountsDataUpdate
{
    const PRODUCT_COUNTER_TABLE = 'clarobi_product_counts';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * ProductCountsDataUpdate constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Add/Update row in table based on product id and column to set/update.
     *
     * @param $productId
     * @param $productAutoIncrement
     * @param integer $count
     * @param string $column Possible values: 'views' or 'adds_to_cart'.
     * @param string $operation Possible value: '+' or '-'
     */
    public function updateProductCountersTable($productId, $productAutoIncrement, $count, $column, $operation = '+')
    {
        $date = date('Y-m-d H:i:s', time());

        $sql = "INSERT INTO " . self::PRODUCT_COUNTER_TABLE
            . " (`product_id`, `product_auto_increment`, `{$column}`,`created_at`)
                VALUES (?,?,?,?)
                ON DUPLICATE KEY
                UPDATE `{$column}`  =  `{$column}` {$operation} ?, `updated_at` = ?";

        try {
            $productIdBinary = Uuid::fromHexToBytes($productId);
            $this->connection->executeUpdate(
                $sql,
                [$productIdBinary, $productAutoIncrement, $count, $date, $count, $date]
            );
        } catch (DBALException $exception) {
            /**
             * @todo log in db.log file
             */
            die($exception->getMessage());
        }
    }
}

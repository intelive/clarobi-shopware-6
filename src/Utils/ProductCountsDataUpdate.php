<?php declare(strict_types=1);

namespace ClarobiClarobi\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class ProductCountsHelper
 * This is called when one of the events take place and product counts table needs to be updated.
 *
 * @package ClarobiClarobi\Utils
 */
class ProductCountsDataUpdate
{
    /** @var Connection $connection */
    private $connection;

    protected static $productCountersTable = 'clarobi_product_counts';
    public static $itemAddition = '+';
    public static $itemSubtraction = '-';
    public static $itemView = 'views';
    public static $itemAddToCart = 'adds_to_cart';

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
     * @param string $column
     * @param string $operation
     * @return bool
     */
    public function updateProductCountersTable($productId, $productAutoIncrement, $count, $column, $operation = '+')
    {
        $date = date('Y-m-d H:i:s', time());

        $sql = "INSERT INTO " . self::$productCountersTable
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
            return false;
        }
    }
}

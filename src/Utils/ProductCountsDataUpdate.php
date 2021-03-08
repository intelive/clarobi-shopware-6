<?php declare(strict_types=1);

namespace ClarobiClarobi\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
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
        try {
            $date = date('Y-m-d H:i:s', time());
            $productIdBinary = Uuid::fromHexToBytes($productId);

            switch ($column) {
                case self::$itemView:
                    $sql = "INSERT IGNORE INTO `clarobi_product_counts`
                                (`product_id`, `product_auto_increment`, `views`,`created_at`)
                            VALUES (:productId,:productAutoId, :valueToInsert,:createdAt)";
                    if ($operation === self::$itemAddition) {
                        $sql .= "   ON DUPLICATE KEY UPDATE views = views + :increaseWith , `updated_at` = :updatedAt;";
                    }
                    break;
                case self::$itemAddToCart:
                    $sql = "INSERT IGNORE INTO `clarobi_product_counts`
                                (`product_id`, `product_auto_increment`, `adds_to_cart`,`created_at`)
                            VALUES (:productId,:productAutoId, :valueToInsert,:createdAt)
                            ON DUPLICATE KEY";
                    switch ($operation) {
                        case self::$itemAddition:
                            $sql .= "   UPDATE adds_to_cart = adds_to_cart + :increaseWith , `updated_at` = :updatedAt;";
                            break;
                        case self::$itemSubtraction:
                            $sql .= "   UPDATE adds_to_cart = adds_to_cart - :increaseWith , `updated_at` = :updatedAt;";
                            break;
                        default:
                            return false;
                    }
                    break;
                default:
                    return false;
            }

            $this->connection->executeQuery(
                $sql,
                [
                    'productId' => $productIdBinary,
                    'productAutoId' => $productAutoIncrement,
                    'valueToInsert' => $count,
                    'createdAt' => $date,
                    'increaseWith' => $count,
                    'updatedAt' => $date,
                ],
                [
                    'productId' => ParameterType::BINARY,
                    'productAutoId' => ParameterType::INTEGER,
                    'valueToInsert' => ParameterType::INTEGER,
                    'createdAt' => ParameterType::STRING,
                    'increaseWith' => ParameterType::INTEGER,
                    'updatedAt' => ParameterType::STRING,
                ]
            );
        } catch (\Exception $exception) {
            return false;
        }
    }
}

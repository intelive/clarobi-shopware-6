<?php declare(strict_types=1);

namespace ClarobiClarobi\Utils;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\MultiInsertQueryQueue;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class AutoIncrementHelper
 *
 * @package ClarobiClarobi\Utils
 * @author Georgiana Camelia Gitan (g.gitan@interlive.ro)
 */
class AutoIncrementHelper
{
    const ENTITY_TYPE_CART = "cart";
    const ENTITY_TYPE_DOCUMENT = "document";

    /** @var Connection $connection */
    protected $connection;

    /**
     * AutoIncrementHelper constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $type Possible values:
     * @param $data
     */
    public function createAutoIncrementForEntity($type, $data): void
    {
        try {
            switch ($type) {
                case self::ENTITY_TYPE_CART:
                    $sql = <<<SQL
INSERT IGNORE INTO `clarobi_entity_auto_increment` (`entity_type`, `entity_token`)
    VALUES (:entityType, :entityData);
SQL;
                    $this->connection->executeQuery(
                        $sql,
                        [
                            'entityType' => $type,
                            'entityData' => $data,
                        ],
                        [
                            'entityType' => ParameterType::STRING,
                            'entityToken' => ParameterType::STRING,
                        ]
                    )->fetchAll();

                    break;
                case self::ENTITY_TYPE_DOCUMENT:
                    $queue = new MultiInsertQueryQueue($this->connection, count($data), true);
                    foreach ($data as $datum) {
                        $insert = [
                            'entity_type' => $type,
                            'entity_id' => Uuid::fromHexToBytes($datum['id']),
                            'entity_token' => $datum['name']
                        ];
                        $queue->addInsert('clarobi_entity_auto_increment', $insert);
                    }
                    $queue->execute();

                    break;
                default:
                    return;
            }
        } catch (\Exception $exception) {
            return;
        }
    }

    public function getLastAbandonedCartId()
    {
        // DAL not used: Query operation of JOIN with custom table 'clarobi_entity_auto_increment'
        // No need for prepared statement since there is not user input
        $result = $this->connection->executeQuery("
                 SELECT `entity_auto_increment`, `entity_token` FROM `clarobi_entity_auto_increment` claro
                 LEFT JOIN `cart` ON claro.`entity_token` = cart.`token`
                 WHERE DATE(cart.`created_at`) <= DATE_SUB(DATE(NOW()), INTERVAL 2 DAY)
                 ORDER BY claro.`entity_auto_increment` DESC LIMIT 1;
        ")->fetchAll();

        return (isset($result[0]) ? (int)$result[0]['entity_auto_increment'] : 0);
    }

    /**
     * @param string $documentType
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDocLastAutoInc($documentType)
    {
        $sql = <<<SQL
 SELECT * FROM `clarobi_entity_auto_increment`
 WHERE `entity_type` = :entityType
     AND `entity_token` = :entityToken
 ORDER BY `entity_auto_increment` DESC LIMIT 1;
SQL;
        $result = $this->connection->executeQuery(
            $sql,
            [
                'entityType' => self::ENTITY_TYPE_DOCUMENT,
                'entityToken' => $documentType,
            ],
            [
                'entityType' => ParameterType::STRING,
                'entityToken' => ParameterType::STRING,
            ]
        )->fetchAll();

        return (isset($result[0]) ? (int)$result[0]['entity_auto_increment'] : 0);
    }
}

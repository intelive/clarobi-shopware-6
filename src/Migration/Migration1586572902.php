<?php declare(strict_types=1);

namespace ClarobiClarobi\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1586572902 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1586572902;
    }

    /**
     * Create `clarobi_entity_auto_increment` table on plugin install.
     * Used for saving auto_increment id for cart and document entities.
     *
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(Connection $connection): void
    {
        $connection->executeUpdate("
            DROP TABLE IF EXISTS `clarobi_entity_auto_increment`;
        ");
        $connection->executeUpdate("
            CREATE TABLE IF NOT EXISTS `clarobi_entity_auto_increment` (
                    `entity_type` ENUM('cart','document') NOT NULL,
                    `entity_auto_increment` INTEGER(11) NOT NULL AUTO_INCREMENT,
                    `entity_id` BINARY(16) DEFAULT NULL UNIQUE,
                    `entity_token` VARCHAR(100) DEFAULT NULL,
                    PRIMARY KEY (`entity_auto_increment`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Remove auto_increment column from `cart` table on plugin uninstall.
     *
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}

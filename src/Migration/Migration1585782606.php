<?php declare(strict_types=1);

namespace ClarobiClarobi\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1585782606 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1585782606;
    }

    /**
     * Create `clarobi_product_counts` table on plugin install.
     *
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(Connection $connection): void
    {
        $connection->executeUpdate("
            DROP TABLE IF EXISTS `clarobi_product_counts`;
        ");
        $connection->executeUpdate("
            CREATE TABLE IF NOT EXISTS `clarobi_product_counts` (
                    `product_id` BINARY(16) NOT NULL,
                    `product_auto_increment` INTEGER(11) NOT NULL,
                    `views` INT DEFAULT 0,
                    `adds_to_cart` INT DEFAULT 0  ,
                    `created_at` DATETIME(3) NOT NULL,
                    `updated_at` DATETIME(3) NULL,
                    PRIMARY KEY (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Drop `clarobi_product_counts` table on plugin uninstall.
     *
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}

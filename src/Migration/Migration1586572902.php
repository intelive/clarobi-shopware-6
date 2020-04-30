<?php declare(strict_types=1);

namespace Clarobi\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1586572902 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1586572902;
    }

    /**
     * Add auto_increment column to `cart` table on plugin install.
     * New column name: `clarobi_auto_increment`
     *
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(Connection $connection): void
    {
        $connection->executeUpdate('
            ALTER TABLE `cart`
            ADD COLUMN `clarobi_auto_increment` INTEGER(11) unsigned NOT NULL AUTO_INCREMENT UNIQUE
            AFTER `name`;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // todo implement drop column on uninstall/delete plugin
    }
}

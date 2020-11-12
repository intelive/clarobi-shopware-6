<?php declare(strict_types=1);

namespace ClarobiClarobi\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1586573547 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1586573547;
    }

    /**
     * Add auto_increment column to `document` table on plugin install.
     * New column name: `clarobi_auto_increment`
     * !!!Note: column is not added as extension to DocumentEntity!!!
     *
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(Connection $connection): void
    {
        $query = <<<SQL
                    ALTER TABLE `document`
                    ADD COLUMN `clarobi_auto_increment` INTEGER(11) unsigned NOT NULL AUTO_INCREMENT UNIQUE;
SQL;

        $connection->executeUpdate($query);
    }

    /**
     * Remove auto_increment column from `document` table on plugin uninstall.
     *
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateDestructive(Connection $connection): void
    {
    }
}

<?php declare(strict_types=1);

namespace ClarobiClarobi;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class ClarobiClarobi extends Plugin
{
    /**
     * Undo all the database changes made on install.
     *
     * @param UninstallContext $uninstallContext
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $queries = [];
        $queries[] = <<<SQL
                    DROP TABLE IF EXISTS `clarobi_product_counts`;
SQL;
        $queries[] = <<<SQL
                    ALTER TABLE `cart` DROP COLUMN `clarobi_auto_increment`;
SQL;
        $queries[] = <<<SQL
                    ALTER TABLE `document` DROP COLUMN `clarobi_auto_increment`;
SQL;
        foreach ($queries as $query) {
            try {
                $connection->executeQuery($query);
            } catch (DBALException $e) {
                continue;
            }
        }
    }
}

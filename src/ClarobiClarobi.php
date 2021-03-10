<?php declare(strict_types=1);

namespace ClarobiClarobi;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

/**
 * Class ClarobiClarobi
 *
 * @package ClarobiClarobi
 * @author Georgiana Camelia Gitan (g.gitan@interlive.ro)
 */
class ClarobiClarobi extends Plugin
{
    /**
     * Undo all the database changes made on install.
     *
     * @param UninstallContext $uninstallContext
     * @throws DBALException
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $connection->executeUpdate('
            DROP TABLE IF EXISTS `clarobi_product_counts`;
        ');
        $connection->executeUpdate('
            DROP TABLE IF EXISTS `clarobi_entity_auto_increment`;
        ');
    }
}

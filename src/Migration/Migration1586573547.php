<?php declare(strict_types=1);

namespace ClarobiClarobi\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Document\DocumentGenerator\CreditNoteGenerator;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1586573547 extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1586573547;
    }

    /**
     * Insert existing documents and carts to the new table for mapping auto_increment column.
     *
     * @param Connection $connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(Connection $connection): void
    {
        $connection->executeUpdate("
             INSERT IGNORE INTO `clarobi_entity_auto_increment` (`entity_type`, `entity_token`)
                SELECT 'cart', `token` FROM `cart`;
        ");

        $connection->executeUpdate("
             INSERT IGNORE INTO `clarobi_entity_auto_increment` (`entity_type`, `entity_id`,`entity_token`)
                ( SELECT 'document', document.`id`, document_type.`technical_name` FROM `document`
                    JOIN `document_type` ON document.`document_type_id` = document_type.`id`
                    WHERE document_type.`technical_name`
                    IN ('" . InvoiceGenerator::INVOICE . "', '" . CreditNoteGenerator::CREDIT_NOTE . "')
                );
        ");
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

<?php declare(strict_types=1);

namespace Clarobi;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\Indexer\InheritanceIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\MessageQueue\IndexerMessageSender;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class Clarobi extends Plugin
{
    public function activate(ActivateContext $activateContext): void
    {
        $indexerMessageSender = $this->container->get(IndexerMessageSender::class);
        $indexerMessageSender->partial(new \DateTimeImmutable(), [InheritanceIndexer::getName()]);
    }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }

        $connection = $this->container->get(Connection::class);

        $connection->executeUpdate('DROP TABLE IF EXISTS `clarobi_product_counts`');
    }
}

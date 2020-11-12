<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use Doctrine\DBAL\Connection;
use ClarobiClarobi\Service\ClarobiConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use ClarobiClarobi\Core\Framework\Controller\ClarobiAbstractController;

/**
 * Class ClarobiDataCountersController
 * @package ClarobiClarobi\Core\Api
 */
class ClarobiDataCountersController extends ClarobiAbstractController
{
    /** @var Connection $connection */
    protected $connection;
    /** @var ClarobiConfigService $config */
    protected $config;
    /** @var EntityRepositoryInterface $productRepository */
    protected $productRepository;
    /** @var EntityRepositoryInterface $orderRepository */
    protected $orderRepository;

    protected static $selectAutoInc = 'SELECT `auto_increment` as id FROM ';
    protected static $selectClaroAutoInc = 'SELECT `clarobi_auto_increment` as id FROM ';
    protected static $orderByAutoInc = ' ORDER BY `auto_increment` DESC LIMIT 1;';
    protected static $orderByClaroAutoInc = ' ORDER BY `clarobi_auto_increment` DESC LIMIT 1;';
    protected static $docTypeInvoice = 'invoice';
    protected static $docTypeCreditNote = 'credit_note';

    /**
     * ClarobiDataCountersController constructor.
     *
     * @param Connection $connection
     * @param ClarobiConfigService $config
     */
    public function __construct(Connection $connection, ClarobiConfigService $config,
                                EntityRepositoryInterface $productRepo, EntityRepositoryInterface $orderRepo
    )
    {
        $this->config = $config;
        $this->connection = $connection;
        $this->productRepository = $productRepo;
        $this->orderRepository = $orderRepo;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/clarobi/dataCounters", name="clarobi.action.data.counters", methods={"GET"})
     */
    public function dataCountersAction()
    {
        try {
            $customerQueryResult = $this->connection->executeQuery(
                self::$selectAutoInc . '`customer`' . self::$orderByAutoInc
            )->fetch();
            $abandonedCartQueryResult = $this->connection->executeQuery(
                self::$selectClaroAutoInc . '`cart`' . self::$orderByClaroAutoInc
            )->fetch();
            $invoiceQueryResult = $this->connection->executeQuery(
                self::$selectClaroAutoInc . '`document`'
                . " JOIN `document_type` ON document.document_type_id = document_type.id
                    WHERE  document_type.`technical_name` = '" . self::$docTypeInvoice . "'"
                . self::$orderByClaroAutoInc
            )->fetch();
            $creditNoteQueryResult = $this->connection->executeQuery(
                self::$selectClaroAutoInc . '`document`'
                . " JOIN `document_type` ON document.document_type_id = document_type.id
                    WHERE  document_type.`technical_name` = '" . self::$docTypeCreditNote . "'"
                . self::$orderByClaroAutoInc
            )->fetch();
        } catch (Doctrine\DBAL\DBALException $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }

        $lastProduct = $this->productRepository->search(new Criteria(), Context::createDefaultContext())->last();
        $lastOrder = $this->orderRepository->search(new Criteria(), Context::createDefaultContext())->last();

        return new JsonResponse([
            'product' => ($lastProduct ? (int)$lastProduct->getAutoIncrement() : 0),
            'customer' => ($customerQueryResult ? (int)$customerQueryResult['id'] : 0),
            'order' => ($lastOrder ? (int)$lastOrder->getAutoIncrement() : 0),
            'abandonedcart' => ($abandonedCartQueryResult ? (int)$abandonedCartQueryResult['id'] : 0),
            'invoice' => ($invoiceQueryResult ? (int)$invoiceQueryResult['id'] : 0),
            'creditNote' => ($creditNoteQueryResult ? (int)$creditNoteQueryResult['id'] : 0),
        ]);
    }
}

<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Clarobi\Service\ClarobiConfigService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;

/**
 * Class ClarobiDataCountersController
 * @package Clarobi\Core\Api
 */
class ClarobiDataCountersController extends ClarobiAbstractController
{
    // auto_increment
    const SELECT = 'SELECT `auto_increment` as id FROM ';
    const ORDER_BY = ' ORDER BY `auto_increment` DESC LIMIT 1; ';

    const SELECT_CART = 'SELECT `clarobi_auto_increment` as id FROM ';
    const ORDER_BY_CART = ' ORDER BY `clarobi_auto_increment` DESC LIMIT 1; ';

    const SELECT_DOC = 'SELECT document.`clarobi_auto_increment` as id FROM ';
    const ORDER_BY_DOC = ' ORDER BY document.`clarobi_auto_increment` DESC LIMIT 1; ';
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ClarobiConfigService
     */
    protected $config;

    /**
     * ClarobiDataCountersController constructor.
     *
     * @param Connection $connection
     * @param ClarobiConfigService $config
     */
    public function __construct(Connection $connection, ClarobiConfigService $config)
    {
        $this->config = $config;
        $this->connection = $connection;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/clarobi/dataCounters", name="clarobi.action.data.counters", methods={"GET"})
     *
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function dataCountersAction()
    {
        $productQueryResult = $this->connection->executeQuery(self::SELECT . '`product`' . self::ORDER_BY)
            ->fetch();
        $customerQueryResult = $this->connection->executeQuery(self::SELECT . '`customer`' . self::ORDER_BY)
            ->fetch();
        $orderQueryResult = $this->connection->executeQuery(self::SELECT . '`order`' . self::ORDER_BY)->fetch();
        $abandonedCartQueryResult = $this->connection->executeQuery(
            self::SELECT_CART . '`cart`' . self::ORDER_BY_CART
        )->fetch();
        $invoiceQueryResult = $this->connection->executeQuery(
            self::SELECT_DOC . '`document`'
            . " JOIN `document_type` ON document.document_type_id = document_type.id
                    WHERE  document_type.`technical_name` = 'invoice'"
            . self::ORDER_BY_DOC
        )->fetch();
        $creditNoteQueryResult = $this->connection->executeQuery(
            self::SELECT_DOC . '`document`'
            . " JOIN `document_type` ON document.document_type_id = document_type.id
                    WHERE  document_type.`technical_name` = 'credit_note'"
            . self::ORDER_BY_DOC
        )->fetch();
        return new JsonResponse(
            [
                'product' => ($productQueryResult ? $productQueryResult['id'] : 0),
                'customer' => ($customerQueryResult ? $customerQueryResult['id'] : 0),
                'order' => ($orderQueryResult ? $orderQueryResult['id'] : 0),
                'abandonedcart' => ($abandonedCartQueryResult ? $abandonedCartQueryResult['id'] : 0),
                'invoice' => ($invoiceQueryResult ? $invoiceQueryResult['id'] : 0),
                'creditNote' => ($creditNoteQueryResult ? $creditNoteQueryResult['id'] : 0),
            ]
        );
    }
}

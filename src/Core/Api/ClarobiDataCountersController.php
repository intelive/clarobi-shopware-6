<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Clarobi\Service\ClarobiConfigService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;

class ClarobiDataCountersController extends ClarobiAbstractController
{
    // auto_increment
    const SELECT = 'SELECT `auto_increment` as id FROM ';
    const ORDER_BY = ' ORDER BY `auto_increment` DESC LIMIT 1; ';
    const SELECT2 = 'SELECT `clarobi_auto_increment` as id FROM ';
    const ORDER_BY2 = ' ORDER BY `clarobi_auto_increment` DESC LIMIT 1; ';
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
     */
    public function dataCountersAction()
    {
        /**
         * @todo get cat id after extending it
         */
        $productQuery = $this->connection->executeQuery(self::SELECT . '`product`' . self::ORDER_BY)->fetch();
        $customerQuery = $this->connection->executeQuery(self::SELECT . '`customer`' . self::ORDER_BY)->fetch();
        $orderQuery = $this->connection->executeQuery(self::SELECT . '`order`' . self::ORDER_BY)->fetch();
        $documentQuery = $this->connection->executeQuery(self::SELECT2 . '`document`' . self::ORDER_BY2)->fetch();
        $abandonedCartQuery = $this->connection->executeQuery(self::SELECT2 . '`cart`' . self::ORDER_BY2)->fetch();

        return new JsonResponse(
            [
                'product' => ($productQuery ? $productQuery['id'] : 0),
                'customer' => ($customerQuery ? $customerQuery['id'] : 0),
                'order' => ($orderQuery ? $orderQuery['id'] : 0),
                'abandonedcart' => ($abandonedCartQuery ? $abandonedCartQuery['id'] : 0),
                'document' => ($documentQuery ? $documentQuery['id'] : 0)
            ]
        );
    }
}

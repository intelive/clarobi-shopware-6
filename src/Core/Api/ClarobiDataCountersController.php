<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Clarobi\Service\ClarobiConfigService;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ClarobiDataCountersController extends ClarobiAbstractController
{
    // auto_increment
    const SELECT = 'SELECT `id` FROM ';
    const ORDER_BY = ' ORDER BY `id` DESC LIMIT 1; ';
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ClarobiConfigService
     */
    protected $config;

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
        $documentQuery = $this->connection->executeQuery(self::SELECT . '`document`' . self::ORDER_BY)->fetch();
        $abandonedCartQuery = $this->connection->executeQuery('
            SELECT `created_at`, `cart` FROM `cart`
            ORDER BY `created_at` DESC LIMIT 1;
        ')->fetch();

        $abandonedCart = null;
        if ($abandonedCartQuery) {
            /** @var Cart $abandonedCart */
            $abandonedCart = unserialize($abandonedCartQuery['cart']);
        }
        return new JsonResponse(
            [
                'product' => ($productQuery ? $this->hexToDec($productQuery['id']) : 0),
                'customer' => ($customerQuery ? $this->hexToDec($customerQuery['id']) : 0),
                'order' => ($orderQuery ? $this->hexToDec($orderQuery['id']) : 0),
                'abandonedcart' => 0,
                // todo : delete (replaced by document)
//                'invoice' => 0,
//                'creditmemo' => 0,
                'document' => ($documentQuery ? $this->hexToDec($documentQuery['id']) : 0)
            ]
        );
    }

}

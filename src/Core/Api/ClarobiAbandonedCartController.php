<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\Cart;
use Clarobi\Service\ClarobiConfigService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class ClarobiAbandonedCartController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiAbandonedCartController extends AbstractController
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ClarobiConfigService
     */
    protected $config;

    public function __construct(ClarobiConfigService $config, Connection $connection)
    {
        $this->config = $config;
        $this->connection = $connection;
    }

    /**
     * @Route("/clarobi/abandonedcart", name="clarobi.abandonedcart.list")
     */
    public function listAction(): Response
    {
        $resultStatement = $this->connection->executeQuery('
            SELECT * FROM `cart`
            WHERE `created_at` > DATE_SUB(DATE(NOW()), INTERVAL 2 DAY);
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $results = $resultStatement->fetchAll();
        if (count($results) === 0) {
            die('nothing');
        }

        /** @var Cart[] $carts */
        $carts = [];
        foreach ($results as $result) {
            /** @var Cart $cart */
            $cart = unserialize($result['cart']);
            $carts[] = $cart;
        }

        return new JsonResponse($carts, Response::HTTP_OK);
    }
}

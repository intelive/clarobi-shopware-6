<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\EncodeResponseService;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\Cart;
use Clarobi\Service\ClarobiConfigService;
use Symfony\Component\HttpFoundation\Request;
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
     * @var EncodeResponseService
     */
    protected $encodeResponse;

    /**
     * @var ClarobiConfigService
     */
    protected $configService;

    public function __construct(
        Connection $connection,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * @Route("/clarobi/abandonedcart", name="clarobi.abandonedcart.list")
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request): JsonResponse
    {
        /**
         * @todo extend cart by adding id column
         */
        try {
            // Get carts created 2 days ago
            $resultStatement = $this->connection->executeQuery('
                    SELECT * FROM `cart`
                    WHERE DATE (`created_at`) = DATE_SUB(DATE(NOW()), INTERVAL 2 DAY);
            ');
            $results = $resultStatement->fetchAll();

            /** @var Cart[] $carts */
            $carts = [];
            if ($results) {

                foreach ($results as $result) {
                    /** @var Cart $cart */
                    $cart = unserialize($result['cart']);
                    $carts[] = $cart;
                }
            }
        } catch (\Exception $exception) {
            return new JsonResponse($exception->getMessage());
        }

        return new JsonResponse([
            'total' => count($carts),
            'carts' => $carts
        ]);
//        return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::ENTITY_NAME));
    }
}

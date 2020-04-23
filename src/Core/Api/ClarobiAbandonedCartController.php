<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\Cart;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;

/**
 * Class ClarobiAbandonedCartController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiAbandonedCartController extends ClarobiAbstractController
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

    const ENTITY_NAME = 'abandonedcart';
    const IGNORED_KEYS = [
        'name',
        'token',
//        'price',
        'lineItems',
        'errors',
        'deliveries',
        'transactions',
        'modified',
        'extensions',
    ];

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
        try {
            // Verify request
            $this->verifyParam($request);
            $this->verifyToken($request, $this->configService->getConfigs());
            // Get param
            $from_id = $request->get('from_id');

            // Get carts created 2 days ago
            $resultStatement = $this->connection->executeQuery('
                    SELECT * FROM `cart`
                    WHERE DATE (`created_at`) <= DATE_SUB(DATE(NOW()), INTERVAL 2 DAY)
                        AND `clarobi_auto_increment` >= ' . $from_id . '
                    ORDER BY `clarobi_auto_increment` ASC
                    LIMIT 25;
            ');
            $results = $resultStatement->fetchAll();

            $mappedEntities = [];
            $lastId = 0;
            if ($results) {
                foreach ($results as $result) {
                    /** @var Cart $cart */
                    $cart = unserialize($result['cart']);

                    $mappedCart = $this->mapCartEntity($cart->jsonSerialize());
                    $mappedCart['clarobi_auto_increment'] = $result['clarobi_auto_increment'];

                    $mappedEntities[] = $mappedCart;
                }
                $lastId = ($result ? $result['clarobi_auto_increment'] : 0);
            }

            return new JsonResponse($this->encodeResponse->encodeResponse(
                $mappedEntities,
                self::ENTITY_NAME,
                $lastId
            ));

        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @param $cart
     * @return mixed
     */
    private function mapCartEntity($cart)
    {
        $mappedKeys['entity_name'] = self::ENTITY_NAME;

        foreach ($cart as $key => $value) {
            if (in_array($key, self::IGNORED_KEYS)) {
                continue;
            }
            $mappedKeys[$key] = $value;
        }

        $mappedKeys['lineItems'] = [];
        /** @var LineItem $lineItem */
        foreach ($cart['lineItems'] as $lineItem){
            // todo get product based on id
            // todo get product auto_increment
            // todo get product sku - productName
            $mappedKeys['lineItems'][] = [
                'price' => $lineItem->getPrice(),
                'quantity' => $lineItem->getQuantity(),
                'id' => $lineItem->getId(),
                'name' => $lineItem->getLabel(),
            ];
        }

        return $mappedKeys;
    }
}

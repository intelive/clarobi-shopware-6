<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Cart\Cart;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

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

    protected $productRepository;

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

    /**
     * @todo add mapping on multiple levels
     */

    /**
     * ClarobiAbandonedCartController constructor.
     *
     * @param Connection $connection
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(
        Connection $connection,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService,
        EntityRepositoryInterface $productRepository
    )
    {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/clarobi/abandonedcart", name="clarobi.abandonedcart.list")
     *
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
                    LIMIT 50;
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
        foreach ($cart['lineItems'] as $lineItem) {
            $criteria = new Criteria([$lineItem->getId()]);
            /** @var ProductEntity $product */
            $product = $this->productRepository->search($criteria, Context::createDefaultContext())->first();

            $mappedKeys['lineItems'][] = [
                'price' => $lineItem->getPrice(),
                'quantity' => $lineItem->getQuantity(),
                'id' => $product->getAutoIncrement(),
                'sku' => $product->getProductNumber(),
                'name' => $lineItem->getLabel(),
            ];
        }

        return $mappedKeys;
    }
}

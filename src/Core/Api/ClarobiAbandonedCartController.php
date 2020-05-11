<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Checkout\Cart\Cart;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Shopware\Core\Framework\Uuid\Uuid;
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
    /**
     * @var EntityRepositoryInterface
     */
    protected $productRepository;

    const ENTITY_NAME = 'abandonedcart';

    const IGNORED_KEYS = [
//        'price', 'lineItems',
        'name', 'token', 'errors', 'deliveries', 'transactions', 'modified', 'extensions',
    ];

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
    public function listAction(Request $request)
    {
        try {
            // Verify request
            $this->verifyParam($request);
            $this->verifyToken($request, $this->configService->getConfigs());
            // Get param
            $from_id = $request->get('from_id');

            // Get carts created 2 days ago
            $stm = $this->connection->executeQuery("
                    SELECT cart.`*`FROM `cart`
                    WHERE cart.`clarobi_auto_increment` >= {$from_id}
                        AND DATE(cart.`created_at`) = DATE_SUB(DATE(NOW()), INTERVAL 2 DAY)
                    ORDER BY cart.`clarobi_auto_increment` ASC
                    LIMIT 50;
            ");
            $results = $stm->fetchAll();

            $mappedEntities = [];
            $lastId = 0;
            if ($results) {
                foreach ($results as $result) {
                    $mappedEntities[] = $this->mapCartEntity($result);
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
    private function mapCartEntity($result)
    {
        /** @var Cart $cart */
        $cart = unserialize($result['cart']);
        $cart = $cart->jsonSerialize();

        $mappedKeys = $this->ignoreEntityKeys($cart, self::ENTITY_NAME, self::IGNORED_KEYS);

        $mappedKeys['clarobi_auto_increment'] = $result['clarobi_auto_increment'];
        $mappedKeys['customerId'] = $this->getCustomerAutoIncrement($result['customer_id']);
        $mappedKeys['createdAt'] = $result['created_at'];
        $mappedKeys['salesChannelId'] = Uuid::fromBytesToHex($result['sales_channel_id']);

        $mappedKeys['lineItems'] = [];
        /** @var LineItem $lineItem */
        foreach ($cart['lineItems'] as $lineItem) {
            // Do not get promotions or other line item type
            if ($lineItem->getType() == 'product') {
                $criteria = new Criteria([$lineItem->getId()]);
                /** @var ProductEntity $product */
                $product = $this->productRepository->search($criteria, Context::createDefaultContext())->first();

                $item = $lineItem->jsonSerialize();
                $item['product'] = $product;

                $mappedKeys['lineItems'][] = $item;
            }
        }

        return $mappedKeys;
    }

    private function getCustomerAutoIncrement($customerId)
    {
        if ($customerId) {
            $customerId = Uuid::fromBytesToHex($customerId);
            $stm = $this->connection->executeQuery("
                    SELECT `auto_increment` FROM `customer`
                    WHERE customer.`id` = 0x" . $customerId . "
                    LIMIT 1;
            ");
            $result = $stm->fetchAll();

            if ($result[0]) {
                return $result[0]['auto_increment'];
            }
        }

        return null;
    }
}

<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Cart\Cart;
use ClarobiClarobi\Service\ClarobiConfigService;
use ClarobiClarobi\Service\EncodeResponseService;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use ClarobiClarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class ClarobiAbandonedCartController
 *
 * @package ClarobiClarobi\Core\Api
 * @author Georgiana Camelia Gitan (g.gitan@interlive.ro)
 */
class ClarobiAbandonedCartController extends ClarobiAbstractController
{
    /** @var Connection $connection */
    protected $connection;
    /** @var EncodeResponseService $encodeResponse */
    protected $encodeResponse;
    /** @var ClarobiConfigService $configService */
    protected $configService;
    /** @var EntityRepositoryInterface $productRepository */
    protected $productRepository;
    protected $customerRepository;

    protected static $entityName = 'abandonedcart';
    protected static $ignoreKeys = [
        'name', 'token', 'errors', 'deliveries', 'transactions', 'modified', 'extensions',
    ];

    /**
     * ClarobiAbandonedCartController constructor.
     *
     * @param Connection $connection
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(Connection $connection, ClarobiConfigService $configService,
                                EncodeResponseService $responseService, EntityRepositoryInterface $productRepository,
                                EntityRepositoryInterface $customerRepository
    )
    {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/clarobi/abandonedcart", name="clarobi.abandonedcart.list", methods={"GET"})
     */
    public function listAction(Request $request): JsonResponse
    {
        try {
            $this->context = $request->get(self::$contextKey);

            $this->verifyParam($request);
            $this->verifyToken($request, $this->configService->getConfigs());
            $from_id = $request->get('from_id');

            // Get carts created 2 days ago
            // DAL not used: Query operation with JOIN on custom table 'clarobi_entity_auto_increment'
            $sql = <<<SQL
SELECT * FROM `cart`
LEFT JOIN `clarobi_entity_auto_increment` claro ON claro.`entity_token` = cart.`token`
WHERE claro.`entity_auto_increment` >= :fromId
    AND DATE(cart.`created_at`) = DATE_SUB(DATE(NOW()), INTERVAL 2 DAY)
ORDER BY claro.`entity_auto_increment` ASC
LIMIT 50;
SQL;
            $results = $this->connection->executeQuery(
                $sql, ['fromId' => $from_id], ['fromId' => ParameterType::INTEGER]
            )->fetchAll();

            $mappedEntities = [];
            $lastId = 0;
            if ($results) {
                foreach ($results as $result) {
                    $mappedEntities[] = $this->mapCartEntity($result);
                }
                $lastId = ($result ? $result['entity_auto_increment'] : 0);
            }

            return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::$entityName, $lastId));
        } catch (\Throwable $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Map cart.
     *
     * @param $result
     * @return array
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidLengthException
     */
    private function mapCartEntity($result)
    {
        /** @var Cart $cart */
        $cart = unserialize($result['cart']);
        $cart = $cart->jsonSerialize();

        $mappedKeys = $this->ignoreEntityKeys($cart, self::$entityName, self::$ignoreKeys);

        $mappedKeys['clarobi_auto_increment'] = $result['entity_auto_increment'];
        $mappedKeys['customerId'] = $this->getCustomerAutoIncrement($result['customer_id']);
        $mappedKeys['createdAt'] = $result['created_at'];
        $mappedKeys['salesChannelId'] = Uuid::fromBytesToHex($result['sales_channel_id']);

        $mappedKeys['lineItems'] = [];
        /** @var LineItem $lineItem */
        foreach ($cart['lineItems'] as $lineItem) {
            // Get line items of type product
            if ($lineItem->getType() == 'product') {
                $criteria = new Criteria([$lineItem->getId()]);

                /** @var ProductEntity $product */
                $product = $this->productRepository->search($criteria, $this->context)->first();

                $item = $lineItem->jsonSerialize();
                $item['product'] = $product;
                $mappedKeys['lineItems'][] = $item;
            }
        }

        return $mappedKeys;
    }

    /**
     * Return customer auto_increment from UUID.
     *
     * @param $customerId
     * @return int|null
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidException
     * @throws \Shopware\Core\Framework\Uuid\Exception\InvalidUuidLengthException
     */
    private function getCustomerAutoIncrement($customerId)
    {
        if ($customerId) {
            $customerId = Uuid::fromBytesToHex($customerId);
            $criteria = new Criteria([$customerId]);
            $criteria->setLimit(1);
            /** @var CustomerEntity $customer */
            $customer = $this->customerRepository->search($criteria, $this->context)->first();
            if ($customer) {
                return $customer->getAutoIncrement();
            }
        }
        return null;
    }
}

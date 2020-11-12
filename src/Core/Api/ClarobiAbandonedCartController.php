<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Context;
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
                                EncodeResponseService $responseService, EntityRepositoryInterface $productRepository
    )
    {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
        $this->productRepository = $productRepository;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/clarobi/abandonedcart", name="clarobi.abandonedcart.list", methods={"GET"})
     */
    public function listAction(Request $request)
    {
        try {
            $this->verifyParam($request);
            $this->verifyToken($request, $this->configService->getConfigs());
            $from_id = $request->get('from_id');

            // Get carts created 2 days ago
            $query = <<<SQL
                        SELECT cart.`*`FROM `cart`
                        WHERE cart.`clarobi_auto_increment` >= {$from_id}
                            AND DATE(cart.`created_at`) = DATE_SUB(DATE(NOW()), INTERVAL 2 DAY)
                        ORDER BY cart.`clarobi_auto_increment` ASC
                        LIMIT 50;
SQL;
            $stm = $this->connection->executeQuery($query);
            $results = $stm->fetchAll();

            $mappedEntities = [];
            $lastId = 0;
            if ($results) {
                foreach ($results as $result) {
                    $mappedEntities[] = $this->mapCartEntity($result);
                }
                $lastId = ($result ? $result['clarobi_auto_increment'] : 0);
            }

            return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::$entityName, $lastId));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Map cart.
     *
     * @param $cart
     * @return mixed
     */
    private function mapCartEntity($result)
    {
        /** @var Cart $cart */
        $cart = unserialize($result['cart']);
        $cart = $cart->jsonSerialize();

        $mappedKeys = $this->ignoreEntityKeys($cart, self::$entityName, self::$ignoreKeys);

        $mappedKeys['clarobi_auto_increment'] = $result['clarobi_auto_increment'];
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
                $product = $this->productRepository->search($criteria, Context::createDefaultContext())->first();

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
     * @return mixed|null
     */
    private function getCustomerAutoIncrement($customerId)
    {
        if ($customerId) {
            try {
                $customerId = Uuid::fromBytesToHex($customerId);
                $query = <<<SQL
                        SELECT `auto_increment` FROM `customer`
                        WHERE customer.`id` = 0x" . $customerId . "
                        LIMIT 1;
SQL;
                $stm = $this->connection->executeQuery($query);
                $result = $stm->fetchAll();
                if ($result[0]) {
                    return $result[0]['auto_increment'];
                }
            } catch (DBALException $e) {
                return null;
            }
        }
        return null;
    }
}

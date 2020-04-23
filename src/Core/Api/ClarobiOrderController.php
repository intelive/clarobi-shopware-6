<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\Context;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Checkout\Order\OrderCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Class ClarobiOrderController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiOrderController extends ClarobiAbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var EncodeResponseService
     */
    protected $encodeResponse;

    /**
     * @var ClarobiConfigService
     */
    protected $configService;

    const ENTITY_NAME = 'sales_order';

    const IGNORED_KEYS = [
//        'id', 'autoIncrement', 'orderNumber', 'currencyId', 'orderDateTime', 'orderDate', 'price', 'amountTotal',
//        'amountNet', 'orderCustomer', 'currency', 'addresses', 'deliveries', 'lineItems', 'transactions',
//        'createdAt', 'updatedAt',
        'currencyFactor', 'salesChannelId', 'billingAddressId', 'positionPrice', 'taxStatus', 'shippingCosts',
        'shippingTotal', 'languageId', 'language', 'salesChannel', 'deepLinkCode', 'stateMachineState', 'stateId',
        'customFields', 'documents', 'tags', 'affiliateCode', 'campaignCode', '_uniqueIdentifier', 'versionId',
        'translated', 'extensions', 'billingAddressVersionId',
    ];

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        $this->orderRepository = $orderRepository;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * @Route("/clarobi/order", name="clarobi.order.list")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request): JsonResponse
    {
        try {
            // Verify token request
            $this->verifyParam($request);
            $this->verifyToken($request, $this->configService->getConfigs());
            // Get param request
            $from_id = $request->get('from_id');

            $context = Context::createDefaultContext();
            $criteria = new Criteria();
            $criteria->setLimit(1)
                ->addFilter(new RangeFilter('autoIncrement', ['gte' => $from_id]))
                ->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING))
                ->addAssociation('lineItems.product.categories')
                ->addAssociation('addresses')
                ->addAssociation('addresses.country')
                ->addAssociation('addresses.state')
                ->addAssociation('deliveries')
                ->addAssociation('transactions.paymentMethod');

            /**
             * @todo add association for discount code
             * "shippingTotal": 10 - is calculated if shipping settings are set
             */

            /** @var OrderCollection $entities */
            $entities = $this->orderRepository->search($criteria, $context);

            $mappedEntities = [];
            /** @var OrderEntity $element */
            foreach ($entities->getElements() as $element) {
                $mappedEntities[] = $this->mapOrderEntity($element->jsonSerialize());
            }
            $lastId = $element->getAutoIncrement();

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
     * @param $order
     * @return mixed
     */
    private function mapOrderEntity($order)
    {
        $mappedKeys['entity_name'] = self::ENTITY_NAME;
        foreach ($order as $key => $value) {
            if (in_array($key, self::IGNORED_KEYS)) {
                continue;
            }
            $mappedKeys[$key] = $value;
        }

        return $mappedKeys;
    }
}

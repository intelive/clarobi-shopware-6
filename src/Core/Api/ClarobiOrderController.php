<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
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
//        'id', 'autoIncrement', 'orderNumber', 'currencyId', 'orderDateTime', 'orderDate',
//        'price', 'amountTotal', 'shippingCosts',
//        'amountNet', 'orderCustomer', 'currency', 'lineItems',
//        'createdAt', 'updatedAt', 'shippingTotal',
        'transactions',

        'deliveries',
        'addresses',
        'currencyFactor', 'salesChannelId',
        'billingAddressId',
        'positionPrice', 'taxStatus',
        'stateMachineState',
        'languageId', 'language', 'salesChannel', 'deepLinkCode', 'stateId',
        'customFields', 'documents', 'tags', 'affiliateCode', 'campaignCode', '_uniqueIdentifier', 'versionId',
        'translated', 'extensions', 'billingAddressVersionId',
    ];

    const IGNORED_KEYS_LEVEL_1 = [
        'price' => [],
        'orderCustomer' => [],
//        'addresses' => [],
//        'deliveries' => [],
        'lineItems' => [],
//        'transactions' => []
    ];

    /**
     * ClarobiOrderController constructor.
     *
     * @param EntityRepositoryInterface $orderRepository
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
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
            $criteria->setLimit(50)
                ->addFilter(new RangeFilter('autoIncrement', ['gte' => $from_id]))
                ->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING))
                ->addAssociation('lineItems.product.categories')
                ->addAssociation('lineItems.product.properties')
                ->addAssociation('lineItems.product.parent')
                ->addAssociation('deliveries.shippingMethod')
//                ->addAssociation('deliveries.shippingOrderAddress.country')
//                ->addAssociation('deliveries.shippingOrderAddress.countryState')
                ->addAssociation('addresses.country')
                ->addAssociation('addresses.countryState')
                ->addAssociation('transactions.paymentMethod')
                ->addAssociation('orderCustomer.customer.group')
                ->addAssociation('currency');

            /**
             * @todo load product parent
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

        // Get order status
        $mappedKeys['status'] = $order['stateMachineState']->getTechnicalName();

        /** @var OrderTransactionCollection $transactions */
        $transactions = $order['transactions'];
        $mappedKeys['paymentMethod'] = $transactions->last()->getPaymentMethod()->getName();

        /** @var OrderDeliveryCollection $deliveries */
        $deliveries = $order['deliveries'];

        // Get order shipping description
        $mappedKeys['shippingDescription'] = $deliveries->last()->getShippingMethod()->getDescription();

        // Get billing and shipping address separate
        $shippingOrderAddressId = $deliveries->last()->getShippingOrderAddressId();
        /** @var OrderAddressCollection $addresses */
        $addresses = $order['addresses'];
        foreach ($addresses->getElements() as $element) {
            if ($element->getId() == $order['billingAddressId']) {
                $mappedKeys['billingAddress'] = $element;
            }
            if ($element->getId() == $shippingOrderAddressId) {
                $mappedKeys['shippingAddress'] = $element;
            }
        }

        // If line item is of type 'product' add parent
        // More mapping may be done to reduce data
        /** @var OrderLineItemEntity $lineItem */
//        foreach ($order['lineItems'] as $lineItem) {
//            $serialize = $lineItem->jsonSerialize();
//            if($lineItem->getType() == 'product'){
//                $serialize['parent'] = $lineItem->getPa
//            }
//        }

        /**
         * Add options to every line item
         * "options":{
         *      "attribute_id": "1",
         *      "item_id": "381", #order item id,
         *      "label": "Manufacturer",
         *      "value": "Made In China"
         * }
         */

        return $mappedKeys;
    }
}

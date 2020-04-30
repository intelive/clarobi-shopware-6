<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Utils\ProductMapperHelper;
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
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;

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
    protected $encoder;

    /**
     * @var ClarobiConfigService
     */
    protected $configService;

    protected $mapperHelper;

    const ENTITY_NAME = 'sales_order';

    const IGNORED_KEYS = [
//        'id', 'autoIncrement', 'orderNumber',
//      'orderDateTime', 'orderDate',
//        'price', 'amountTotal', 'shippingCosts',
//        'amountNet', 'orderCustomer',
//        'createdAt', 'updatedAt', 'shippingTotal',

        'currencyId',
        'currency',
        'lineItems',
        'transactions',
        'deliveries',
        'addresses',
        'currencyFactor',
//        'salesChannelId',
        'billingAddressId',
        'positionPrice', 'taxStatus',
        'stateMachineState',
        'languageId', 'language', 'salesChannel', 'deepLinkCode', 'stateId',
        'customFields', 'documents', 'tags', 'affiliateCode', 'campaignCode', '_uniqueIdentifier', 'versionId',
        'translated', 'extensions', 'billingAddressVersionId',
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
        EncodeResponseService $responseService,
        ProductMapperHelper $mapperHelper
    )
    {
        $this->orderRepository = $orderRepository;
        $this->configService = $configService;
        $this->encoder = $responseService;
        $this->mapperHelper = $mapperHelper;
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
                ->addFilter(new RangeFilter('autoIncrement', ['gt' => $from_id]))
                ->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING))
                ->addAssociation('lineItems.product.categories')
                ->addAssociation('lineItems.product.properties.group.translations')
                ->addAssociation('lineItems.product.options.group.translations')
//                ->addAssociation('lineItems.product.parent')
                ->addAssociation('deliveries.shippingMethod')
//                ->addAssociation('deliveries.shippingOrderAddress.country')
//                ->addAssociation('deliveries.shippingOrderAddress.countryState')
                ->addAssociation('addresses.country')
                ->addAssociation('addresses.countryState')
                ->addAssociation('transactions.paymentMethod')
                ->addAssociation('orderCustomer.customer.group')
                ->addAssociation('currency');

            /** @var OrderCollection $entities */
            $entities = $this->orderRepository->search($criteria, $context);

            $mappedEntities = [];
            $lastId = 0;
            if($entities->getElements()){
                /** @var OrderEntity $element */
                foreach ($entities->getElements() as $element) {
                    $mappedEntities[] = $this->mapOrderEntity($element->jsonSerialize());
                }
                $lastId = $element->getAutoIncrement();
            }

            return new JsonResponse($this->encoder->encodeResponse(
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
     * @throws \Doctrine\DBAL\DBALException
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

        // Get currency
        $mappedKeys['currency_isoCode'] = $order['currency']->getIsoCode();
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

        // Get mapped line items
        $mappedKeys['lineItems'] = $this->mapperHelper->mapOrderLineItems($order);

        return $mappedKeys;
    }
}

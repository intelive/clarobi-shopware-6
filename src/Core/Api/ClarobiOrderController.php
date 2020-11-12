<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use ClarobiClarobi\Utils\ProductMapperHelper;
use Shopware\Core\Framework\Context;
use ClarobiClarobi\Service\ClarobiConfigService;
use ClarobiClarobi\Service\EncodeResponseService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Checkout\Order\OrderCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use ClarobiClarobi\Core\Framework\Controller\ClarobiAbstractController;
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
 * @package ClarobiClarobi\Core\Api
 */
class ClarobiOrderController extends ClarobiAbstractController
{
    /** @var EntityRepositoryInterface $orderRepository */
    protected $orderRepository;
    /** @var EncodeResponseService $encoder */
    protected $encoder;
    /** @var ClarobiConfigService $configService */
    protected $configService;
    /** @var ProductMapperHelper $mapperHelper */
    protected $mapperHelper;

    protected static $entityName = 'sales_order';
    protected static $ignoreKeys = [
        'currencyId', 'currency', 'lineItems', 'transactions', 'deliveries', 'addresses', 'currencyFactor',
        'billingAddressId', 'positionPrice', 'taxStatus', 'stateMachineState', 'languageId', 'language',
        'salesChannel', 'deepLinkCode', 'stateId', 'customFields', 'documents', 'tags', 'affiliateCode',
        'campaignCode', '_uniqueIdentifier', 'versionId', 'translated', 'extensions', 'billingAddressVersionId',
    ];

    /**
     * ClarobiOrderController constructor.
     *
     * @param EntityRepositoryInterface $orderRepository
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(EntityRepositoryInterface $orderRepository, ClarobiConfigService $configService,
                                EncodeResponseService $responseService, ProductMapperHelper $mapperHelper
    )
    {
        $this->orderRepository = $orderRepository;
        $this->configService = $configService;
        $this->encoder = $responseService;
        $this->mapperHelper = $mapperHelper;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/clarobi/order", name="clarobi.order.list", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        try {
            $this->verifyParam($request);
            $this->verifyToken($request, $this->configService->getConfigs());
            $from_id = $request->get('from_id');

            $context = Context::createDefaultContext();
            $criteria = new Criteria();
            $criteria->setLimit(50)
                ->addFilter(new RangeFilter('autoIncrement', ['gt' => $from_id]))
                ->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING))
                ->addAssociation('lineItems.product.categories')
                ->addAssociation('lineItems.product.properties.group.translations')
                ->addAssociation('lineItems.product.options.group.translations')
                ->addAssociation('deliveries.shippingMethod')
                ->addAssociation('addresses.country')
                ->addAssociation('addresses.countryState')
                ->addAssociation('transactions.paymentMethod')
                ->addAssociation('orderCustomer.customer.group')
                ->addAssociation('currency');

            /** @var OrderCollection $entities */
            $entities = $this->orderRepository->search($criteria, $context);

            $mappedEntities = [];
            $lastId = 0;
            if ($entities->getElements()) {
                /** @var OrderEntity $element */
                foreach ($entities->getElements() as $element) {
                    $mappedEntities[] = $this->mapOrderEntity($element->jsonSerialize());
                }
                $lastId = $element->getAutoIncrement();
            }

            return new JsonResponse($this->encoder->encodeResponse($mappedEntities, self::$entityName, $lastId));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Map order entity.
     *
     * @param $order
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    private function mapOrderEntity($order)
    {
        $mappedKeys = $this->ignoreEntityKeys($order, self::$entityName, self::$ignoreKeys);

        $mappedKeys['currency_isoCode'] = $order['currency']->getIsoCode();
        $mappedKeys['status'] = $order['stateMachineState']->getTechnicalName();
        /** @var OrderTransactionCollection $transactions */
        $transactions = $order['transactions'];
        $mappedKeys['paymentMethod'] = $transactions->last()->getPaymentMethod()->getName();
        /** @var OrderDeliveryCollection $deliveries */
        $deliveries = $order['deliveries'];
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
        $mappedKeys['lineItems'] = $this->mapperHelper->mapOrderLineItems($order);

        return $mappedKeys;
    }
}

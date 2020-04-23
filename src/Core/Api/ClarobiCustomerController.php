<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\Context;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Class ClarobiCustomerController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiCustomerController extends ClarobiAbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var EncodeResponseService
     */
    protected $encodeResponse;

    /**
     * @var ClarobiConfigService
     */
    protected $configService;

    const ENTITY_NAME = 'customer';
    const IGNORED_KEYS = [
//        'id', 'autoIncrement', 'firstName', 'lastName', 'email', 'guest', 'createdAt', 'title', 'group',
//        'defaultBillingAddress', 'defaultShippingAddress', 'birthday',
        'salutation', 'groupId', 'defaultPaymentMethodId', 'salesChannelId', 'languageId', 'lastPaymentMethodId',
        'defaultBillingAddressId', 'defaultShippingAddressId', 'customerNumber', 'salutationId', 'company', 'password',
        'affiliateCode', 'campaignCode', 'active', 'doubleOptInRegistration', 'doubleOptInEmailSentDate',
        'doubleOptInConfirmDate', 'hash', 'firstLogin', 'lastLogin', 'newsletter', 'lastOrderDate', 'orderCount',
        'updatedAt', 'legacyEncoder', 'legacyPassword', 'defaultPaymentMethod', 'language', 'lastPaymentMethod',
        'activeBillingAddress', 'activeShippingAddress', 'addresses', 'orderCustomers', 'tags', 'promotions',
        'recoveryCustomer', 'customFields', 'productReviews', 'remoteAddress', '_uniqueIdentifier', 'versionId',
        'translated', 'extensions', 'salesChannel',
    ];

    /**
     * ClarobiCustomerController constructor.
     *
     * @param EntityRepositoryInterface $customerRepository
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(
        EntityRepositoryInterface $customerRepository,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        /**
         * @todo : duplicate code - how can ge changed?
         */
        $this->customerRepository = $customerRepository;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * @Route("/clarobi/customer", name="clarobi.customer.list")
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
                ->addAssociation('group')
                ->addAssociation('salutation')
                ->addAssociation('defaultBillingAddress.country')
                ->addAssociation('defaultShippingAddress.country');

            /** @var EntityCollection $entities */
            $entities = $this->customerRepository->search($criteria, $context);

            $mappedEntities = [];
            /** @var CustomerEntity $element */
            foreach ($entities->getElements() as $element) {
                $mappedEntities[] = $this->mapCustomerEntity($element->jsonSerialize());
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
     * @param $customer
     * @return mixed
     */
    private function mapCustomerEntity($customer)
    {
        $mappedKeys['entity_name'] = self::ENTITY_NAME;
        foreach ($customer as $key => $value) {
            if (in_array($key, self::IGNORED_KEYS)) {
                continue;
            }
            $mappedKeys[$key] = $value;
        }
        /** @var SalutationEntity $salutation */
        $salutation = $customer['salutation'];
        /**
         * Possible values: not_specified, mr, mrs
         */
        $mappedKeys['salutation'] = $salutation->getSalutationKey();

        /**
         * @todo set store_id to a default value ?
         */
//        $mappedKeys['store_id'] = 1;

        return $mappedKeys;
    }
}

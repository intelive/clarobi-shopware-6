<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use Shopware\Core\Framework\Context;
use ClarobiClarobi\Service\ClarobiConfigService;
use ClarobiClarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use ClarobiClarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Class ClarobiCustomerController
 *
 * @package ClarobiClarobi\Core\Api
 */
class ClarobiCustomerController extends ClarobiAbstractController
{
    /** @var EntityRepositoryInterface $customerRepository */
    protected $customerRepository;
    /** @var EncodeResponseService $encodeResponse */
    protected $encodeResponse;
    /** @var ClarobiConfigService $configService */
    protected $configService;

    protected static $entityName = 'customer';
    protected static $ignoreKeys = [
        'salutation', 'groupId', 'defaultPaymentMethodId', 'languageId', 'lastPaymentMethodId',
        'defaultBillingAddressId', 'defaultShippingAddressId', 'customerNumber', 'salutationId', 'company', 'password',
        'affiliateCode', 'campaignCode', 'active', 'doubleOptInRegistration', 'doubleOptInEmailSentDate',
        'doubleOptInConfirmDate', 'hash', 'firstLogin', 'lastLogin', 'newsletter', 'lastOrderDate', 'orderCount',
        'updatedAt', 'legacyEncoder', 'legacyPassword', 'defaultPaymentMethod', 'language', 'lastPaymentMethod',
        'activeBillingAddress', 'activeShippingAddress', 'addresses', 'orderCustomers', 'tags', 'promotions',
        'recoveryCustomer', 'customFields', 'productReviews', 'remoteAddress', '_uniqueIdentifier', 'versionId',
        'translated', 'extensions',
        'salesChannel',
    ];

    /**
     * ClarobiCustomerController constructor.
     *
     * @param EntityRepositoryInterface $customerRepository
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(EntityRepositoryInterface $customerRepository, ClarobiConfigService $configService,
                                EncodeResponseService $responseService
    )
    {
        $this->customerRepository = $customerRepository;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/clarobi/customer", name="clarobi.customer.list", methods={"GET"})
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
                ->addAssociation('group')
                ->addAssociation('salutation')
                ->addAssociation('defaultBillingAddress.country')
                ->addAssociation('defaultShippingAddress.country');

            /** @var EntityCollection $entities */
            $entities = $this->customerRepository->search($criteria, $context);

            $mappedEntities = [];
            $lastId = 0;
            if ($entities->getElements()) {
                /** @var CustomerEntity $element */
                foreach ($entities->getElements() as $element) {
                    $mappedEntities[] = $this->mapCustomerEntity($element->jsonSerialize());
                }
                $lastId = $element->getAutoIncrement();
            }

            return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::$entityName, $lastId));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Map customer entity.
     *
     * @param $customer
     * @return mixed
     */
    private function mapCustomerEntity($customer)
    {
        $mappedKeys = $this->ignoreEntityKeys($customer, self::$entityName, self::$ignoreKeys);

        /** @var SalutationEntity $salutation */
        $salutation = $customer['salutation'];
        $mappedKeys['salutation'] = $salutation->getSalutationKey();    // Possible values: not_specified, mr, mrs

        return $mappedKeys;
    }
}

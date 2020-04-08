<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfig;
use OpenApiFixures\Customer;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;

/**
 * Class ClarobiCustomerController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiCustomerController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $customerRepository;

    public function __construct(EntityRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @Route("/clarobi/customer", name="clarobi.customer.list")
     */
    public function listAction(): Response
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(10)
            ->addFilter(new RangeFilter('autoIncrement', ['gte' => 1]))
            ->addSorting(new FieldSorting('autoIncrement'));

        /** @var EntityCollection $entities */
        $entities = $this->customerRepository->search($criteria, $context);

        /**
         * @todo map entities
         * @todo catch errors
         * @todo return empty data
         * @todo encode data
         */
        if (($entities->count()) === 0) {
            return new JsonResponse(
                ['No orders found matching given criteria.'],
                Response::HTTP_OK
            );
        }

        $mappedEntities = [];
        /** @var CustomerEntity $element */
        foreach ($entities->getElements() as $element) {
            $mappedEntities[$element->getId()] = [
                'name' => $element->getFirstName(),
                'autoIncrement' => $element->getAutoIncrement()
            ];
        }
        return new JsonResponse($mappedEntities, Response::HTTP_OK);
    }
}

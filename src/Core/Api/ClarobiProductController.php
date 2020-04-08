<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfig;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;

/**
 * Class ClarobiProductController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiProductController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $productRepository;

    public function __construct(EntityRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/clarobi/product", name="clarobi.product.list")
     */
    public function listAction(): Response
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(10)
            ->addFilter(new RangeFilter('autoIncrement', ['gte' => 1]))
        ->addSorting(new FieldSorting('autoIncrement'));

        /** @var EntityCollection $entities */
        $entities = $this->productRepository->search($criteria, $context);

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
        /** @var ProductEntity $element */
        foreach ($entities->getElements() as $element) {
            $mappedEntities[$element->getId()] = [
                'name' => $element->getName(),
                'autoIncrement' => $element->getAutoIncrement()
            ];
        }
        return new JsonResponse($mappedEntities, Response::HTTP_OK);
    }
}

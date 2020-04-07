<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfig;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
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
        $criteria->setLimit(2)
            ->addFilter(new EqualsFilter('active', true));
        $productIds = $this->productRepository->searchIds($criteria, $context)->getIds();
//        $productIds = $this->productRepository->search($criteria, $context)->get('auto_increment');

        if (\count($productIds) === 0) {
            return new JsonResponse(
                ['Please create products before by using this route.'],
                Response::HTTP_NO_CONTENT
            );
        }

//        return new JsonResponse($productIds, Response::HTTP_OK);


        /** @var EntityCollection $entities */
        $entities = $this->productRepository->search($criteria, $context);

        return new JsonResponse($entities, Response::HTTP_OK);
    }
}

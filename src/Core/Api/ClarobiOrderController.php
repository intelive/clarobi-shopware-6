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
 * Class ClarobiOrderController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiOrderController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $orderRepository;

    public function __construct(EntityRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @Route("/clarobi/order", name="clarobi.order.list")
     */
    public function listAction(): Response
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(2);
        $orderIds = $this->orderRepository->searchIds($criteria, $context)->getIds();
//        $orderIds = $this->orderRepository->search($criteria, $context)->get('auto_increment');

        if (\count($orderIds) === 0) {
            return new JsonResponse(
                ['Please create orders before by using this route.'],
                Response::HTTP_NO_CONTENT
            );
        }

//        return new JsonResponse($orderIds, Response::HTTP_OK);


        /** @var EntityCollection $entities */
        $entities = $this->orderRepository->search($criteria, $context);

        return new JsonResponse($entities, Response::HTTP_OK);
    }
}

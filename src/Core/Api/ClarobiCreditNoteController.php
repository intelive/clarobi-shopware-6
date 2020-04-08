<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;

/**
 * Class ClarobiCreditNoteController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiCreditNoteController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $documentRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $documentTypeRepository;

    public function __construct(
        EntityRepositoryInterface $documentRepository,
        EntityRepositoryInterface $documentTypeRepository
    )
    {
        $this->documentRepository = $documentRepository;
        $this->documentTypeRepository = $documentTypeRepository;
    }

    /**
     * @Route("/clarobi/creditmemo", name="clarobi.creditmemo.list")
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request): JsonResponse
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(10)
            ->addFilter(new EqualsFilter('documentType.technicalName', 'credit_note'));

        /** @var EntityCollection $entities */
        $entities = $this->documentRepository->search($criteria, $context);

        return new JsonResponse($entities, Response::HTTP_OK);
    }
}

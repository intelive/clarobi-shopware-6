<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfig;
use Shopware\Core\Checkout\Document\DocumentService;
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
 * Class ClarobiInvoiceController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiInvoiceController extends AbstractController
{
    /**
     * @var DocumentService
     */
    protected $documentService;

    /**
     * @var EntityRepositoryInterface
     */
    private $documentRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $documentTypeRepository;

    /**
     * @todo delete DocumentService if not needed
     *
     * ClarobiInvoiceController constructor.
     * @param EntityRepositoryInterface $documentRepository
     * @param EntityRepositoryInterface $documentTypeRepository
     */
    public function __construct(
//        DocumentService $documentService,
        EntityRepositoryInterface $documentRepository,
        EntityRepositoryInterface $documentTypeRepository
    )
    {
//        $this->documentService = $documentService;
        $this->documentRepository = $documentRepository;
        $this->documentTypeRepository = $documentTypeRepository;
    }

    /**
     * @Route("/clarobi/invoice", name="clarobi.invoice.list")
     */
    public function listAction(): Response
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(10)
            ->addFilter(new EqualsFilter('documentType.technicalName', 'invoice'));

        /** @var EntityCollection $entities */
        $entities = $this->documentRepository->search($criteria, $context);

        return new JsonResponse($entities, Response::HTTP_OK);
//        CriteriaFactory.equals('documentType.technicalName', 'invoice');
    }
}

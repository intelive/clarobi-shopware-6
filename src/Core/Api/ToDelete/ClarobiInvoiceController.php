<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfig;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
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
     * @var EncodeResponseService
     */
    protected $encodeResponse;

    /**
     * @var ClarobiConfigService
     */
    protected $configService;

    /**
     * @param EntityRepositoryInterface $documentRepository
     * @param EntityRepositoryInterface $documentTypeRepository
     * @todo delete DocumentService if not needed
     *
     * ClarobiInvoiceController constructor.
     */
    public function __construct(
//        DocumentService $documentService,
        EntityRepositoryInterface $documentRepository,
        EntityRepositoryInterface $documentTypeRepository,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
//        $this->documentService = $documentService;
        $this->documentRepository = $documentRepository;
        $this->documentTypeRepository = $documentTypeRepository;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * @todo delete - DocumentsController will be used for invoice and credit note
     *
     * @Route("/clarobi/invoice", name="clarobi.invoice.list")
     */
    public function listAction(): Response
    {
        // orderDocuments endpoint
        // delete invoice and

        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(10)
            ->addFilter(new EqualsFilter('documentType.technicalName', 'invoice'));

        /** @var EntityCollection $entities */
        $entities = $this->documentRepository->search($criteria, $context);

        return new JsonResponse($entities, Response::HTTP_OK);
//        return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::ENTITY_NAME));
    }
}

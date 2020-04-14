<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Clarobi\Service\EncodeResponseService;
use Clarobi\Service\ClarobiConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ClarobiDocumentsController
 *
 * @package Clarobi\Core\Api
 * @RouteScope(scopes={"storefront"})
 */
class ClarobiDocumentsController extends ClarobiAbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $documentRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $documentTypeRepository;
    /**
     * @var ClarobiConfigService
     */
    protected $config;

    /**
     * @var EncodeResponseService
     */
    protected $encodeResponse;

    public function __construct(
        EntityRepositoryInterface $documentRepository,
        EntityRepositoryInterface $documentTypeRepository,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        $this->documentRepository = $documentRepository;
        $this->documentTypeRepository = $documentTypeRepository;
        $this->config = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * @Route("/clarobi/documents", name="clarobi.documents.list", methods={"GET"})
     */
    public function listAction(Request $request)
    {
        /**
         * @todo filter documents by type in claro_app
         */

        try {
            // Verify request
            $this->verifyRequest($request, $this->config->getConfigs());
            // Get param
            $from_id = $request->get('from_id');

            $from_id = $this->decToHex($from_id);
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }

        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(10)
            ->addFilter(new RangeFilter('id', ['gte' => $from_id]))
            ->addSorting(new FieldSorting('id', FieldSorting::ASCENDING));

        /** @var EntityCollection $entities */
        $entities = $this->documentRepository->search($criteria, $context);

        $mappedEntities = [];
        foreach ($entities->getElements() as $element) {
            $mappedEntities[] = ['id' => $this->hexToDec($element->getId())];
        }

        return new JsonResponse($mappedEntities, Response::HTTP_OK);
//        return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::ENTITY_NAME));
    }
}

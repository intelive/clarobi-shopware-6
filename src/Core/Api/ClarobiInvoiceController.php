<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ClarobiInvoiceController
 * @package Clarobi\Core\Api
 */
class ClarobiInvoiceController extends ClarobiBaseDocumentController
{
    const ENTITY_NAME = 'sales_invoice';
    const DOC_TYPE = 'invoice';

    /**
     * ClarobiDocumentsController constructor.
     *
     * @param Connection $connection
     * @param EntityRepositoryInterface $documentRepository
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(
        Connection $connection,
        EntityRepositoryInterface $documentRepository,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        parent::__construct($connection, $documentRepository, $configService, $responseService);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/clarobi/invoice", name="clarobi.invoice.list", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        try {
            // Verify request
            $this->verifyParam($request);
            $this->verifyToken($request, $this->config->getConfigs());
            // Get param
            $from_id = $request->get('from_id');

            $this->getDocumentIdsByType(self::DOC_TYPE, $from_id);
            $invoicesCollection = $this->getDocumentCollectionFromIds($this->hexIds);

            $mappedEntities = [];
            $lastId = 0;

            if ($invoicesCollection) {
                /** @var DocumentEntity $element */
                foreach ($invoicesCollection as $element) {
                    $mappedEntities[] = $this->ignoreEntityKeys(
                        $element->jsonSerialize(),
                        self::ENTITY_NAME
                    );
                }
                $lastId = $this->incrementIds[$element->getId()];
            }

            return new JsonResponse($this->encodeResponse->encodeResponse(
                $mappedEntities,
                self::ENTITY_NAME,
                $lastId
            ));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }
}

<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class ClarobiCreditNoteController
 * @package Clarobi\Core\Api
 */
class ClarobiCreditNoteController extends ClarobiBaseDocumentController
{
    const ENTITY_NAME = 'sales_creditnote';
    const DOC_TYPE = 'credit_note';

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
     * @Route("/clarobi/creditNote", name="clarobi.credit.note.list", methods={"GET"})
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
            $creditNotesCollection = $this->getDocumentCollectionFromIds($this->hexIds);

            $mappedEntities = [];
            $lastId = 0;

            if ($creditNotesCollection) {
                /** @var DocumentEntity $element */
                foreach ($creditNotesCollection as $element) {
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

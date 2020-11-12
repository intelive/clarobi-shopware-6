<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use Doctrine\DBAL\Connection;
use ClarobiClarobi\Service\ClarobiConfigService;
use ClarobiClarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class ClarobiCreditNoteController
 *
 * @package ClarobiClarobi\Core\Api
 */
class ClarobiCreditNoteController extends ClarobiBaseDocumentController
{
    protected static $entityName = 'sales_creditnote';
    protected static $documentType = 'credit_note';

    /**
     * ClarobiDocumentsController constructor.
     *
     * @param Connection $connection
     * @param EntityRepositoryInterface $documentRepository
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(Connection $connection, EntityRepositoryInterface $documentRepository,
                                ClarobiConfigService $configService, EncodeResponseService $responseService
    )
    {
        parent::__construct($connection, $documentRepository, $configService, $responseService);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/clarobi/creditNote", name="clarobi.credit.note.list", methods={"GET"})
     */
    public function listAction(Request $request)
    {
        try {
            $this->verifyParam($request);
            $this->verifyToken($request, $this->config->getConfigs());
            $from_id = $request->get('from_id');

            $this->getDocumentIdsByType(self::$documentType, $from_id);
            $creditNotesCollection = $this->getDocumentCollectionFromIds($this->hexIds);

            $mappedEntities = [];
            $lastId = 0;
            if ($creditNotesCollection) {
                /** @var DocumentEntity $element */
                foreach ($creditNotesCollection as $element) {
                    $mappedEntities[] = $this->mapDocumentEntity(
                        $element->jsonSerialize(),
                        self::$entityName
                    );
                }
                $lastId = $this->incrementIds[$element->getId()];
            }

            return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::$entityName, $lastId));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }
}

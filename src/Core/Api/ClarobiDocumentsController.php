<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

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

    /**
     * @var Connection
     */
    protected $connection;

    protected $hexIds = [];

    protected $incrementIds = [];

    const ENTITY_NAME = 'document';

    const IGNORED_KEYS = [
//        'id', 'order', 'config', 'sent', 'static', 'documentType', 'createdAt', 'updatedAt',
        'fileType', 'orderId', 'orderVersionId', 'documentTypeId', 'documentMediaFileId', 'deepLinkCode',
        'customFields', 'referencedDocumentId', 'referencedDocument', 'dependentDocuments', 'documentMediaFile',
        '_uniqueIdentifier', 'versionId', 'translated', 'extensions',
    ];

    /**
     * ClarobiDocumentsController constructor.
     *
     * @param Connection $connection
     * @param EntityRepositoryInterface $documentRepository
     * @param EntityRepositoryInterface $documentTypeRepository
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(
        Connection $connection,
        EntityRepositoryInterface $documentRepository,
        EntityRepositoryInterface $documentTypeRepository,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        $this->connection = $connection;
        $this->documentRepository = $documentRepository;
        $this->documentTypeRepository = $documentTypeRepository;
        $this->config = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * @Route("/clarobi/document", name="clarobi.document.list", methods={"GET"})
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

            // Get only 2 types of documents
            $selectIdAutoInc = $this->connection->executeQuery("
                    SELECT d.`id`, d.`clarobi_auto_increment`
                    FROM `document` d
                    JOIN `document_type` dt ON d.document_type_id = dt.id
                    WHERE d.`clarobi_auto_increment` > {$from_id}
                    	AND dt.`technical_name` IN ('invoice','credit_note')
                    ORDER BY d.`clarobi_auto_increment` ASC LIMIT 50 ;
            ");

            $idsResult = $selectIdAutoInc->fetchAll();

            $mappedEntities = [];
            $lastId = 0;

            // If ids array is not empty
            if ($idsResult) {
                // Get array with ids in hex to use them in search
                // Get array with auto increment ids to used them for from_id param
                foreach ($idsResult as $item) {
                    $this->hexIds[] = Uuid::fromBytesToHex($item['id']);
                    $this->incrementIds[Uuid::fromBytesToHex($item['id'])] = $item['clarobi_auto_increment'];
                }

                // Get entities
                $context = Context::createDefaultContext();
                $criteria = new Criteria($this->hexIds);
                $criteria->addAssociation('order')
                    ->addAssociation('order.lineItems')
                    ->addAssociation('order.lineItems.product')
                    ->addAssociation('order.currency');

                /** @var EntityCollection $entities */
                $entities = $this->documentRepository->search($criteria, $context);

                if ($entities->getElements()) {
                    /** @var DocumentEntity $element */
                    foreach ($entities->getElements() as $element) {
                        $mappedEntities[] = $this->mapDocumentEntity($element->jsonSerialize());
                    }
                    $lastId = $this->incrementIds[$element->getId()];
                }
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

    /**
     * @param $document
     * @return array
     */
    private function mapDocumentEntity($document)
    {
        $mappedKeys = [];
        $mappedKeys['entity_name'] = self::ENTITY_NAME;
        $mappedKeys['clarobiAutoIncrement'] = $this->incrementIds[$document['id']];

        foreach ($document as $key => $value) {
            if (in_array($key, self::IGNORED_KEYS)) {
                continue;
            }
            $mappedKeys[$key] = $value;
        }

        return $mappedKeys;
    }
}

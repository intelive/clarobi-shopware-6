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
//        'fileType',
        'orderId', 'orderVersionId', 'documentTypeId', 'documentMediaFileId', 'deepLinkCode', 'customFields',
        'referencedDocumentId', 'referencedDocument', 'dependentDocuments', 'documentMediaFile',
        '_uniqueIdentifier', 'versionId', 'translated', 'extensions',
    ];

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
     * @Route("/clarobi/documents", name="clarobi.documents.list", methods={"GET"})
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request): JsonResponse
    {
        /**
         * @todo filter documents by type in claro_app
         */

        try {
            // Verify request
            $this->verifyParam($request);
            $this->verifyToken($request, $this->config->getConfigs());
            // Get param
            $from_id = $request->get('from_id');

            $selectIdAutoInc = $this->connection->executeQuery('
                    SELECT `id`, `clarobi_auto_increment`
                    FROM `document`
                    WHERE `clarobi_auto_increment` > ' . $from_id . '
                    ORDER BY `clarobi_auto_increment` ASC LIMIT 50 ;
            ');
            $idsResult = $selectIdAutoInc->fetchAll();

            /**
             * Get array with ids in hex to use them in search
             * Get array with auto increment ids to used them for from_id param
             */
            if ($idsResult) {
                foreach ($idsResult as $item) {
                    $this->hexIds[] = Uuid::fromBytesToHex($item['id']);
                    $this->incrementIds[Uuid::fromBytesToHex($item['id'])] = $item['clarobi_auto_increment'];
                }
            }
            $context = Context::createDefaultContext();
            $criteria = new Criteria($this->hexIds);
            $criteria->addAssociation('order');

            /** @var EntityCollection $entities */
            $entities = $this->documentRepository->search($criteria, $context);

            $mappedEntities = [];
            /** @var DocumentEntity $element */
            foreach ($entities->getElements() as $element) {
                $mappedEntities[] = $this->mapDocumentEntity($element->jsonSerialize());
            }
            $lastId = $this->incrementIds[$element->getId()];

            return new JsonResponse($this->encodeResponse->encodeResponse(
                $mappedEntities,
                self::ENTITY_NAME,
                $lastId
            ));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }

//        $resultStatement = $this->connection->executeQuery('
//                SELECT * FROM `document`
//                INNER JOIN `order` ON `order`.id = `document`.order_id
//                ORDER BY `document`.`clarobi_auto_increment` ASC LIMIT 10;
//            ');
//        $results = $resultStatement->fetchAll();
//        if ($results) {
//            /** @var DocumentEntity $result */
//            foreach ($results as $result) {
//                var_dump(($result));
//                die;
//            }
//        }
//        $context = Context::createDefaultContext();
//        $criteria = new Criteria();
//        $criteria->setLimit(10)
//            ->addFilter(new RangeFilter('id', ['gte' => $from_id]))
//            ->addSorting(new FieldSorting('id', FieldSorting::ASCENDING))
//            ->addAssociation('order');
//
//        /** @var EntityCollection $entities */
//        $entities = $this->documentRepository->search($criteria, $context);

    }

    /**
     * @param $document
     * @return array
     */
    private function mapDocumentEntity($document)
    {
        $mappedKeys = [];
        $mappedKeys['entity_name'] = self::ENTITY_NAME;
        $mappedKeys['clarobi_auto_increment'] = $this->incrementIds[$document['id']];
//            ($this->incrementIds[$document['id']] ? $this->incrementIds[$document['id']] : 0);

        foreach ($document as $key => $value) {
            if (in_array($key, self::IGNORED_KEYS)) {
                continue;
            }
            $mappedKeys[$key] = $value;
        }

        return $mappedKeys;
    }
}

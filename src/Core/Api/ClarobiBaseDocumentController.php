<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use ClarobiClarobi\Service\ClarobiConfigService;
use ClarobiClarobi\Service\EncodeResponseService;
use Shopware\Core\Checkout\Document\DocumentCollection;
use ClarobiClarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * Class ClarobiBaseDocumentController
 *
 * @package ClarobiClarobi\Core\Api
 */
class ClarobiBaseDocumentController extends ClarobiAbstractController
{
    /** @var Connection $connection */
    protected $connection;
    /** @var EntityRepositoryInterface $documentRepository */
    protected $documentRepository;
    /** @var ClarobiConfigService $config */
    protected $config;
    /** @var EncodeResponseService $encodeResponse */
    protected $encodeResponse;

    public $hexIds = [];
    public $incrementIds = [];

    public static $documentEntity = 'document';

    protected static $ignoreKeys = [
        'fileType', 'orderId', 'orderVersionId', 'documentTypeId', 'documentMediaFileId', 'deepLinkCode',
        'customFields', 'referencedDocumentId', 'referencedDocument', 'dependentDocuments', 'documentMediaFile',
        '_uniqueIdentifier', 'versionId', 'translated', 'extensions', 'documentType'
    ];

    /**
     * ClarobiDocumentsController constructor.
     *
     * @param Connection $connection
     * @param EntityRepositoryInterface $documentRepository
     */
    public function __construct(Connection $connection, EntityRepositoryInterface $documentRepository,
                                ClarobiConfigService $configService, EncodeResponseService $responseService
    )
    {
        $this->connection = $connection;
        $this->documentRepository = $documentRepository;
        $this->config = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * Get document id based on their type (invoice, credit_note, storno_bill, delivery_note).
     *
     * @param $type
     * @param $specificType
     * @param $from_id
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDocumentIdsByType($type, $specificType, $from_id)
    {
        // Get only 2 types of documents

        // DAL not used: Query operation on custom table 'clarobi_entity_auto_increment'
        $sql = <<<SQL
SELECT `entity_auto_increment`, `entity_id`
FROM `clarobi_entity_auto_increment`
WHERE `entity_auto_increment` > :fromId
	AND `entity_type` = :entityType
	AND `entity_token` = :specificType
ORDER BY `entity_auto_increment` ASC LIMIT 50 ;
SQL;
        $idsResult = $this->connection->executeQuery(
            $sql,
            [
                'fromId' => $from_id,
                'entityType' => $type,
                'specificType' => $specificType,
            ],
            [
                'fromId' => ParameterType::INTEGER,
                'entityType' => ParameterType::STRING,
                'entityToken' => ParameterType::STRING,
            ]
        )->fetchAll();

        if ($idsResult) {
            foreach ($idsResult as $item) {
                $this->hexIds[] = Uuid::fromBytesToHex($item['entity_id']);
                $this->incrementIds[Uuid::fromBytesToHex($item['entity_id'])] = $item['entity_auto_increment'];
            }
        }
    }

    /**
     * Get documents collection from ids.
     *
     * @param array $ids
     * @return DocumentEntity[]
     */
    public function getDocumentCollectionFromIds($ids)
    {
        // Get entities
        $criteria = new Criteria($ids);
        $criteria->addAssociations(['order', 'order.lineItems', 'order.lineItems.product', 'order.currency']);

        /** @var DocumentCollection $entities */
        $entities = $this->documentRepository->search($criteria, $this->context);

        return $entities->getElements();
    }

    /**
     * Map document entity.
     *
     * @param $document
     * @param string $entityName
     * @return array
     */
    public function mapDocumentEntity($document, $entityName)
    {
        $mappedKeys = $this->ignoreEntityKeys($document, $entityName, self::$ignoreKeys);
        $mappedKeys['clarobiAutoIncrement'] = $this->incrementIds[$document['id']];

        return $mappedKeys;
    }
}

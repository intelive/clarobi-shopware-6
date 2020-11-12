<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
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
     * @param $from_id
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDocumentIdsByType($type, $from_id)
    {
        // Get only 2 types of documents
        $selectIdAutoInc = $this->connection->executeQuery("
                    SELECT d.`id`, d.`clarobi_auto_increment`
                    FROM `document` d
                    JOIN `document_type` dt ON d.document_type_id = dt.id
                    WHERE d.`clarobi_auto_increment` > {$from_id}
                    	AND dt.`technical_name` = '{$type}'
                    ORDER BY d.`clarobi_auto_increment` ASC LIMIT 50 ;
            ");

        $idsResult = $selectIdAutoInc->fetchAll();

        if ($idsResult) {
            foreach ($idsResult as $item) {
                $this->hexIds[] = Uuid::fromBytesToHex($item['id']);
                $this->incrementIds[Uuid::fromBytesToHex($item['id'])] = $item['clarobi_auto_increment'];
            }
        }
    }

    /**
     * Get documents collection from ids.
     *
     * @param array $ids
     * @return \Shopware\Core\Checkout\Document\DocumentEntity[]
     */
    public function getDocumentCollectionFromIds($ids)
    {
        // Get entities
        $context = Context::createDefaultContext();
        $criteria = new Criteria($ids);
        $criteria->addAssociation('order')
            ->addAssociation('order.lineItems')
            ->addAssociation('order.lineItems.product')
            ->addAssociation('order.currency');

        /** @var DocumentCollection $entities */
        $entities = $this->documentRepository->search($criteria, $context);

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

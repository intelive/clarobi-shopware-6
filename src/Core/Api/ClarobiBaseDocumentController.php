<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class ClarobiBaseDocumentController extends ClarobiAbstractController
{
    /**
     * @var Connection
     */
    protected $connection;
    /**
     * @var EntityRepositoryInterface
     */
    private $documentRepository;
    /**
     * @var ClarobiConfigService
     */
    protected $config;
    /**
     * @var EncodeResponseService
     */
    protected $encodeResponse;

    public $hexIds = [];
    public $incrementIds = [];

    const IGNORED_KEYS = [
//        'id','order', 'config', 'sent', 'static', 'createdAt', 'updatedAt',
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
    public function __construct(
        Connection $connection,
        EntityRepositoryInterface $documentRepository,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        $this->connection = $connection;
        $this->documentRepository = $documentRepository;
        $this->config = $configService;
        $this->encodeResponse = $responseService;
    }

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
            // Get array with ids in hex to use them in search
            // Get array with auto increment ids to used them for from_id param
            foreach ($idsResult as $item) {
                $this->hexIds[] = Uuid::fromBytesToHex($item['id']);
                $this->incrementIds[Uuid::fromBytesToHex($item['id'])] = $item['clarobi_auto_increment'];
            }
        }
    }

    /**
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
     * @param $document
     * @param string $entityName
     * @return array
     */
    public function mapDocumentEntity($document, $entityName)
    {
        $mappedKeys = $this->ignoreEntityKeys(
            $document,
            $entityName,
            self::IGNORED_KEYS
        );
        $mappedKeys['clarobiAutoIncrement'] = $this->incrementIds[$document['id']];

        return $mappedKeys;
    }
}

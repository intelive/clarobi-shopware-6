<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use ClarobiClarobi\Service\ClarobiConfigService;
use ClarobiClarobi\Service\EncodeResponseService;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ClarobiInvoiceController
 *
 * @package ClarobiClarobi\Core\Api
 */
class ClarobiInvoiceController extends ClarobiBaseDocumentController
{
    protected static $entityName = 'sales_invoice';
    protected static $docType = 'invoice';

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
     * @Route("/clarobi/invoice", name="clarobi.invoice.list", methods={"GET"})
     */
    public function listAction(Request $request)
    {
        try {
            $this->verifyParam($request);
            $this->verifyToken($request, $this->config->getConfigs());
            $from_id = $request->get('from_id');

            $this->getDocumentIdsByType(self::$docType, $from_id);
            $invoicesCollection = $this->getDocumentCollectionFromIds($this->hexIds);

            $mappedEntities = [];
            $lastId = 0;
            if ($invoicesCollection) {
                /** @var DocumentEntity $element */
                foreach ($invoicesCollection as $element) {
                    $mappedEntities[] = $this->mapDocumentEntity($element->jsonSerialize(), self::$entityName);
                }
                $lastId = $this->incrementIds[$element->getId()];
            }

            return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::$entityName, $lastId));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }
}

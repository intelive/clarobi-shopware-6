<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Checkout\Order\OrderEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Checkout\Order\OrderCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;

/**
 * Class DocumentsGeneratorController
 * @package Clarobi\Core\Api
 */
class DocumentsGeneratorController
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
     * @var EntityRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var NumberRangeValueGeneratorInterface
     */
    protected $valueGenerator;

    const TYPE_INVOICE = 'invoice';
    const TYPE_CREDIT_NOTE = 'credit_note';
    const SALES_CHANNEL = '98432def39fc4624b33213a56b8c944d';

    /**
     * @todo for more sample data
     *      run: bin/console framework:demodata
     */

    /**
     * DocumentsGeneratorController constructor.
     * @param EntityRepositoryInterface $documentRepository
     * @param EntityRepositoryInterface $documentTypeRepository
     * @param EntityRepositoryInterface $orderRepository
     */
    public function __construct(
        EntityRepositoryInterface $documentRepository,
        EntityRepositoryInterface $documentTypeRepository,
        EntityRepositoryInterface $orderRepository,
        NumberRangeValueGeneratorInterface $valueGenerator
    )
    {
        $this->documentRepository = $documentRepository;
        $this->documentTypeRepository = $documentTypeRepository;
        $this->orderRepository = $orderRepository;
        $this->valueGenerator = $valueGenerator;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/clarobi/generate/document", name="clarobi.generate.document", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateDocuments(Request $request)
    {
        try {
            $autoIncrement = (int)$request->get('id');
            $limit = (int)$request->get('limit');

            $docTypesData = $this->getDocsTypeData();

            $orderIds = [];

            $criteria = new Criteria();
            $criteria->setLimit($limit)
                ->addFilter(new RangeFilter('autoIncrement', ['gte' => $autoIncrement]))
                ->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING))
                ->addAssociation('documents');

            /** @var OrderCollection $order */
            $orders = $this->orderRepository->search($criteria, Context::createDefaultContext())->getElements();

            /** @var OrderEntity $order */
            foreach ($orders as $order) {
                // Generate invoice and credit_note numbers
                $generatedInvoiceNumber = $this->generateNumber(self::TYPE_INVOICE);
                $generatedCreditNoteNumber = $this->generateNumber(self::TYPE_CREDIT_NOTE);

                // First create invoice
                $this->generateConfigsAndCreate(
                    $order,
                    $docTypesData[self::TYPE_INVOICE],
                    self::TYPE_INVOICE,
                    $generatedInvoiceNumber
                );

                // Create credit note
                $this->generateConfigsAndCreate(
                    $order,
                    $docTypesData[self::TYPE_CREDIT_NOTE],
                    self::TYPE_CREDIT_NOTE,
                    $generatedInvoiceNumber,
                    $generatedCreditNoteNumber
                );
                $orderIds[] = [$order->getAutoIncrement(), $order->getOrderNumber()];
            }

            return new JsonResponse(['status' => 'success', 'message' => 'process completed', 'data' => $orderIds]);
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @return array
     */
    private function getDocsTypeData()
    {
        $docTypesData = [];

        // Get necessary doc types
        $criteria = new Criteria();
        $criteria->addAssociation('documentBaseConfigs')
            ->addFilter(new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new EqualsFilter('technicalName', self::TYPE_INVOICE),
                    new EqualsFilter('technicalName', self::TYPE_CREDIT_NOTE),
                ]
            ));
        $documentTypes = $this->documentTypeRepository->search(
            $criteria,
            Context::createDefaultContext()
        )->getEntities();

        /** @var DocumentTypeEntity $documentType */
        foreach ($documentTypes as $documentType) {
            $tehName = $documentType->getTechnicalName();

            // Get base config and assign custom fields
            $baseConfig = $documentType->getDocumentBaseConfigs()->first();
            $config = $baseConfig->getConfig();
            $config['id'] = $baseConfig->getId();
            $config['name'] = $baseConfig->getName();
            $config['logo'] = $baseConfig->getLogo();
            $config['title'] = null;
            $config['global'] = true;
            $config['custom'] = [];
            $config['extensions'] = [];
            $config['translated'] = [];

            $docTypesData[$tehName] = [
                'docTypeId' => $documentType->getId(),
                'config' => $config
            ];
        }

        return $docTypesData;
    }

    /**
     * @param $type
     * @return string
     */
    private function generateNumber($type)
    {
        // Generate number
        return $this->valueGenerator->getValue(
            'document_' . $type,
            Context::createDefaultContext(),
            self::SALES_CHANNEL,
            false
        );
    }

    /**
     * @param OrderEntity $order
     * @param $docTypeData
     * @param $type
     * @param $invoiceNumber
     * @param null $creditNoteNumber
     */
    private function generateConfigsAndCreate($order, $docTypeData, $type, $invoiceNumber, $creditNoteNumber = null)
    {
        switch ($type) {
            case self::TYPE_INVOICE:
                $docTypeData['config']['custom'] = ['invoiceNumber' => $invoiceNumber];
                break;
            case self::TYPE_CREDIT_NOTE:
                $docTypeData['config']['custom'] = [
                    'invoiceNumber' => $invoiceNumber,
                    'creditNoteNumber' => $creditNoteNumber
                ];
                break;
        }

        // Create new order version for invoice
        $newOrderVersion = $this->orderRepository->createVersion($order->getId(), Context::createDefaultContext());

        // Create document
        $result = $this->documentRepository->create([
            [
                'documentTypeId' => $docTypeData['docTypeId'],
                'fileType' => 'pdf',
                'orderId' => $order->getId(),
                'orderVersionId' => $newOrderVersion,
                'config' => $docTypeData['config'],
                'deepLinkCode' => Uuid::randomHex()
            ]
        ], Context::createDefaultContext());
    }
}

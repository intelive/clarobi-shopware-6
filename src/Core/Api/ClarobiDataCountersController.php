<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use ClarobiClarobi\Utils\AutoIncrementHelper;
use Doctrine\DBAL\Connection;
use ClarobiClarobi\Service\ClarobiConfigService;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Checkout\Document\DocumentGenerator\CreditNoteGenerator;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use ClarobiClarobi\Core\Framework\Controller\ClarobiAbstractController;

/**
 * Class ClarobiDataCountersController
 *
 * @package ClarobiClarobi\Core\Api
 */
class ClarobiDataCountersController extends ClarobiAbstractController
{
    /** @var AutoIncrementHelper $helper */
    protected $helper;
    /** @var ClarobiConfigService $config */
    protected $config;
    /** @var EntityRepositoryInterface $productRepository */
    protected $productRepository;
    /** @var EntityRepositoryInterface $customerRepository */
    protected $customerRepository;
    /** @var EntityRepositoryInterface $orderRepository */
    protected $orderRepository;
    /** @var EntityRepositoryInterface $documentRepository */
    protected $documentRepository;

    /**
     * ClarobiDataCountersController constructor.
     *
     * @param Connection $connection
     * @param ClarobiConfigService $config
     */
    public function __construct(AutoIncrementHelper $helper, ClarobiConfigService $config,
                                EntityRepositoryInterface $productRepo, EntityRepositoryInterface $customerRepo,
                                EntityRepositoryInterface $orderRepo, EntityRepositoryInterface $documentRepo
    )
    {
        $this->config = $config;
        $this->helper = $helper;
        $this->productRepository = $productRepo;
        $this->customerRepository = $customerRepo;
        $this->orderRepository = $orderRepo;
        $this->documentRepository = $documentRepo;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/clarobi/dataCounters", name="clarobi.action.data.counters", methods={"GET"})
     */
    public function dataCountersAction(Request $request): JsonResponse
    {
        try {
            $this->context = $request->get($this::$contextKey);

            return new JsonResponse([
                'product' => $this->getEntityId('product'),
                'customer' => $this->getEntityId('customer'),
                'order' => $this->getEntityId('order'),
                'invoice' => $this->helper->getDocLastAutoInc(InvoiceGenerator::INVOICE),
                'creditNote' => $this->helper->getDocLastAutoInc(CreditNoteGenerator::CREDIT_NOTE),
                'abandonedcart' => $this->helper->getLastAbandonedCartId()
            ]);
        } catch (\Doctrine\DBAL\DBALException $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @param $entityName
     * @return int
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    protected function getEntityId($entityName)
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addSorting(new FieldSorting('autoIncrement', FieldSorting::DESCENDING));
        switch ($entityName) {
            case 'product':
                $entity = $this->productRepository->search($criteria, $this->context)->first();
                break;
            case 'customer':
                $entity = $this->customerRepository->search($criteria, $this->context)->first();
                break;
            case 'order':
                $entity = $this->orderRepository->search($criteria, $this->context)->first();
                break;
            default:
                $entity = null;
                break;
        }
        return ($entity ? (int)$entity->getAutoIncrement() : 0);
    }
}

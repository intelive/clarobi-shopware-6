<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use ClarobiClarobi\Service\ClarobiConfigService;
use ClarobiClarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use ClarobiClarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Class ClarobiStockController
 *
 * @package ClarobiClarobi\Core\Api
 * @author Georgiana Camelia Gitan (g.gitan@interlive.ro)
 */
class ClarobiStockController extends ClarobiAbstractController
{
    /** @var EntityRepositoryInterface $productRepository */
    protected $productRepository;
    /** @var EncodeResponseService $encodeResponse */
    protected $encodeResponse;
    /** @var ClarobiConfigService $configService */
    protected $configService;
    /** @var array $configs */
    protected $configs;

    protected static $entityName = 'stock';
    protected static $entityType = 'STOCK';

    /**
     * ClarobiStockController constructor.
     *
     * @param EntityRepositoryInterface $productRepository
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(
        EntityRepositoryInterface $productRepository,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        $this->productRepository = $productRepository;
        $this->configService = $configService;
        $this->configs = $this->configService->getConfigs();
        $this->encodeResponse = $responseService;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/clarobi/stock", name="clarobi.stock", methods={"GET"})
     */
    public function productCountersAction(Request $request): JsonResponse
    {
        try {
            $this->verifyToken($request, $this->configService->getConfigs());

            $this->context = $request->get(self::$contextKey);
            $criteria = new Criteria();
            $criteria->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING));

            /** @var EntityCollection $entities */
            $entities = $this->productRepository->search($criteria, $this->context);

            $stocks = [];
            /** @var ProductEntity $element */
            foreach ($entities->getElements() as $element) {
                $stocks[] = [
                    'id' => $element->getAutoIncrement(),
                    's' => $element->getStock()
                ];
            }
            $date = date('Y-m-d H:i:s', time());
            $data = [
                'date' => $date,
                'stock' => $stocks
            ];

            return new JsonResponse($this->encodeResponse->encodeResponse(
                $data,
                self::$entityName,
                0,
                self::$entityType
            ));
        } catch (\Throwable$exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }
}

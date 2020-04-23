<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\Context;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class ClarobiStockController extends  ClarobiAbstractController
{
    const ENTITY_NAME = 'stock';
    const ENTITY_TYPE = 'STOCK';

    /**
     * @var EntityRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var EncodeResponseService
     */
    protected $encodeResponse;

    /**
     * @var ClarobiConfigService
     */
    protected $configService;

    protected $configs;

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
     * @Route("/clarobi/stock", name="clarobi.stock", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productCountersAction(Request $request): JsonResponse
    {
        try {
            // Verify request
            $this->verifyToken($request, $this->configService->getConfigs());

            $context = Context::createDefaultContext();
            $criteria = new Criteria();
            $criteria->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING));

            /** @var EntityCollection $entities */
            $entities = $this->productRepository->search($criteria, $context);

            $stocks = [];
            /** @var ProductEntity $element */
            foreach ($entities->getElements() as $element) {
                $stocks[] = [
                    'id' => $element->getAutoIncrement(),
                    'id_product' => $element->getAutoIncrement(),
//                    'quantity'=>$element->getAvailableStock(),    // qty after calculating orders?
                    'quantity'=>$element->getStock()
                ];
            }
            $date = date('Y-m-d H:i:s', time());
            $data = [
                'date' => $date,
                'stock' => $stocks
            ];

            return new JsonResponse($this->encodeResponse->encodeResponse($data, self::ENTITY_NAME, self::ENTITY_TYPE));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }
}

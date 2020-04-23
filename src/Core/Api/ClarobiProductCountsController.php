<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Doctrine\DBAL\Connection;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;

class ClarobiProductCountsController extends ClarobiAbstractController
{
    const ENTITY_NAME = 'product_counter';
    const ENTITY_TYPE = 'PRODUCT_COUNTERS';

    const PRODUCT_COUNTER_TABLE = 'clarobi_product_counts';

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var EncodeResponseService
     */
    protected $encodeResponse;

    /**
     * @var ClarobiConfigService
     */
    protected $configService;
    protected $configs;

    /**
     * ClarobiProductCountsController constructor.
     *
     * @param Connection $connection
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(
        Connection $connection,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->configs = $this->configService->getConfigs();
        $this->encodeResponse = $responseService;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/clarobi/productCounters", name="clarobi.product.counters", methods={"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productCountersAction(Request $request): JsonResponse
    {
        try {
            // Verify request
            $this->verifyToken($request, $this->configService->getConfigs());

            $results = $this->connection->executeQuery("
                SELECT * FROM " . self::PRODUCT_COUNTER_TABLE
                . " ORDER BY `product_auto_increment` ASC;
                ")->fetchAll();

            $date = date('Y-m-d H:i:s', time());
            $data = [
                'date' => $date,
                'counters' => []
            ];
            if ($results) {
                foreach ($results as $result) {
                    $data['counters'][] = [
                        'product_id' => $result['product_auto_increment'],
                        'event_name' => 'catalog_product_view',
                        'viewed' => $result['views']
                    ];
                    $data['counters'][] = [
                        'product_id' => $result['product_auto_increment'],
                        'event_name' => 'catalog_cart_add_product',
                        'viewed' => $result['adds_to_cart']
                    ];
                }
            }
            return new JsonResponse($this->encodeResponse->encodeResponse($data, self::ENTITY_NAME, self::ENTITY_TYPE));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }
}

<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use Doctrine\DBAL\Connection;
use ClarobiClarobi\Service\ClarobiConfigService;
use ClarobiClarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use ClarobiClarobi\Core\Framework\Controller\ClarobiAbstractController;

/**
 * Class ClarobiProductCountsController
 *
 * @package ClarobiClarobi\Core\Api
 */
class ClarobiProductCountsController extends ClarobiAbstractController
{
    /** @var Connection $connection */
    protected $connection;
    /** @var EncodeResponseService $encodeResponse */
    protected $encodeResponse;
    /** @var ClarobiConfigService $configService */
    protected $configService;
    /** @var array $configs */
    protected $configs;

    protected static $entityName = 'product_counter';
    protected static $entityType = 'PRODUCT_COUNTERS';

    /**
     * ClarobiProductCountsController constructor.
     *
     * @param Connection $connection
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $responseService
     */
    public function __construct(Connection $connection, ClarobiConfigService $configService,
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
     * @Route(path="/clarobi/productCounters", name="clarobi.product.counters", methods={"GET"})
     */
    public function productCountersAction(Request $request)
    {
        try {
            $this->verifyToken($request, $this->configService->getConfigs());

            // DAL not used: Query operation on custom table 'clarobi_product_counts'
            $results = $this->connection->executeQuery("
                    SELECT * FROM `clarobi_product_counts`
                    ORDER BY `product_auto_increment` ASC;
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
                        'event_name' => 'checkout_cart_add_product',
                        'viewed' => $result['adds_to_cart']
                    ];
                }
            }
            return new JsonResponse($this->encodeResponse->encodeResponse(
                $data,
                self::$entityName,
                0,
                self::$entityType
            ));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }
}

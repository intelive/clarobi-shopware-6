<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ClarobiProductCountsController extends AbstractController
{
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

    public function __construct(
        Connection $connection,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        $this->connection = $connection;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/clarobi/productCounters", name="clarobi.product.counters", methods={"GET"})
     */
    public function dataCountersAction()
    {
        /**
         * @todo add subscriber for events
         * sales_channel.product.id.search.result.loaded
         * product.search.result.loaded - after the search returned data
         * product.id.search.result.loaded - after the search for the ids has been finished
         */

//        return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::ENTITY_NAME))
    }
}

<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfigService;
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
     * @var ClarobiConfigService
     */
    protected $config;

    public function __construct(ClarobiConfigService $config, Connection $connection)
    {
        $this->config = $config;
        $this->connection = $connection;
    }
    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/clarobi/productCounters", name="clarobi.product.counters", methods={"GET"})
     */
    public function dataCountersAction()
    {
        die('prod count');
    }
}

<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfigService;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ClarobiDataCountersController extends AbstractController
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
     * @Route("/clarobi/dataCounters", name="clarobi.action.data.counters", methods={"GET"})
     */
    public function productCountersAction()
    {
        die('1111');
    }

}

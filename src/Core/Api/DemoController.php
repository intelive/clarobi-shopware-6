<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Service\ClarobiConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;

/**
 * Class DemoController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class DemoController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $entityRepository;

    protected $config;

    /**
     * DemoController constructor.
     *
     * @param EntityRepositoryInterface $entityRepository
     * @param ClarobiConfigService $config
     */
    public function __construct(
        EntityRepositoryInterface $entityRepository,
        ClarobiConfigService $config
    )
    {
        $this->entityRepository = $entityRepository;
        $this->config = $config;
    }

    /**
     * @Route("/clarobi/demo/cont", name="clarobi.demo.cont")
     */
    public function demo(): Response
    {
        /**
         * @todo create monolog channel and log errors in specific file
         */

        var_dump($this->config->getConfigs());
        die;
    }
}

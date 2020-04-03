<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class DemoDataController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class DemoDataController extends AbstractController
{
    //TODO implement fields

    public function __construct()
    {
        //TODO implement
    }


    /**
     * @Route("/clarobi/generate", name="clarobi.generate")
     */
    public function generate(): Response
    {
        return new JsonResponse(['Api route with token auth'], Response::HTTP_OK);
    }
}

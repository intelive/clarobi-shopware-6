<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ClarobiProductImageController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiProductImageController extends AbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $productRepository;

    /**
     * die(self::ERR . $exception->getMessage());
     */
    const ERR = 'ERROR:';
    /**
     * @todo headers to add in response
     */
    const HEADER_UNITYREPORTS = 'UnityReports: OK';
    const HEADER_CLAROBI = 'ClaroBI: OK';

    public function __construct(EntityRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/clarobi/image/id/{id}/w/{w}", name="clarobi.product.image")
     * @param Request $request
     * @return Response
     */
    public function getImage(Request $request): Response
    {
        $id = $request->get('id');
        die('get product image');
    }
}

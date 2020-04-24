<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;

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
    const ERR = 'ERROR: ';
    /**
     * @todo headers to add in response
     */
    const HEADER_UNITYREPORTS = 'UnityReports: OK';
    const HEADER_CLAROBI = 'ClaroBI: OK';

    /**
     * ClarobiProductImageController constructor.
     *
     * @param EntityRepositoryInterface $productRepository
     */
    public function __construct(EntityRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Route("/clarobi/image/id/{id}/w/{w}", name="clarobi.product.image")
     *
     * @param Request $request
     * @return Response
     */
    public function getImage(Request $request): Response
    {

//        try {
//            // Get product
//            $product = wc_get_product($this->id);
//            // If product with id exists
//            if (!$product) {
//                throw new Exception(self::ERR . 'Cannot load product');
//            }
//
//            $image = wp_get_attachment_image_src(
//                get_post_thumbnail_id($product->get_id()),
//                array($this->width, $this->width)
//            );
//            if (!$image) {
//                throw new Exception(self::ERR . 'No image found for this product!');
//            }
//            $image_path = $image[0];
//            // Content
//            $content = file_get_contents($image_path);
//            if (!$content) {
//                throw new Exception(self::ERR . 'Could not load image');
//            }
//
//            $ext = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
//            switch ($ext) {
//                case 'gif':
//                    $type = 'image/gif';
//                    break;
//                case 'jpg':
//                case 'jpeg':
//                    $type = 'image/jpeg';
//                    break;
//                case 'png':
//                    $type = 'image/png';
//                    break;
//                default:
//                    $type = 'unknown';
//                    break;
//            }
//
//            if ($type == 'unknown') {
//                throw new Exception(self::ERR . 'Unknown type!');
//            }
//
//            header('Content-Type:' . $type);
//            header(self::HEADER_UNITYREPORTS);
//            header(self::HEADER_CLAROBI);
//            echo $content;
//
////            die();
//        } catch (Exception $exception) {
//            Clarobi_Logger::errorLog($exception->getMessage(), __METHOD__);
//
//            die($exception->getMessage());
//        }

        try {
            $id = $request->get('id');
            $width = $request->get('w');

            $context = Context::createDefaultContext();
            $criteria = new Criteria();
            $criteria->setLimit(50)->addFilter(new EqualsFilter('autoIncrement', $id))
                ->addAssociation('media');

            /** @var ProductEntity $entities */
            $product = $this->productRepository->search($criteria, $context)->first();
            /** @var ProductMediaCollection $image */
            $mediaCollection = $product->getMedia();
            var_dump($mediaCollection->getElements());
            die;

            /**
             * @todo implement
             */


            $ext = 'png';
            $imageName = 'productImage';

            $headers = array(
//                'Content-Type' => 'image/' . $ext,
//                'Content-Disposition' => 'inline; filename="' . $imageName . '"',
                'UnityReports' => 'OK',
                'ClaroBI' => 'OK'
            );

            return new JsonResponse($product, 200, $headers);
        } catch (\Exception $exception) {
            return new Response(self::ERR . $exception->getMessage());
        }
    }
}

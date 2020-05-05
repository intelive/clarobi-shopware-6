<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;
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

    const ERR = 'ERROR: ';

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
     * @Route("/clarobi/product/get-image/id/{id}/w/{w}", name="clarobi.product.get.image")
     *
     * @param Request $request
     * @return Response
     */
    public function getImage(Request $request): Response
    {
        try {
            $id = (int)$request->get('id');
            /**
             * Not used since thumbnails can not be generated from here
             * @todo delete
             *      or use it to get thumbnail between width and minWidth
             */
            $width = (int)$request->get('w');

            // Product criteria
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('autoIncrement', $id))
                ->addAssociation('media')
                ->setLimit(1);

            // Get product
            /** @var ProductEntity $product */
            $product = $this->productRepository->search($criteria, Context::createDefaultContext())->first();
            if (!$product) {
                throw new \Exception('Cannot load product.');
            }

            // Get product media collection
            /** @var ProductMediaCollection $image */
            $mediaCollection = $product->getMedia();
            if (!$mediaCollection->getElements()) {
                throw new \Exception('No image found for this product.');
            }

            $path = null;
            $minWidth = 400;

            // For every product image
            /** @var ProductMediaEntity $mediaItem */
            foreach ($mediaCollection as $mediaItem) {
                // Get thumbnails
                $thumbnails = $mediaItem->getMedia()->getThumbnails();

                // Search for thumbnail with desired width
                /** @var MediaThumbnailEntity $thumbnail */
                foreach ($thumbnails as $thumbnail) {
                    $thumbnailWidth = $thumbnail->getWidth();
                    if ($thumbnailWidth <= $minWidth) {
                        $minWidth = $thumbnailWidth;

                        $path = $thumbnail->getUrl();
                    }
                }
            }
            if (!$path) {
                throw new \Exception('No image found for this product.');
            }
            $content = file_get_contents($path);
            if (!$content) {
                throw new \Exception('Could not load image.');
            }
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            switch ($extension) {
                case 'gif':
                    $type = 'image/gif';
                    break;
                case 'jpg':
                case 'jpeg':
                    $type = 'image/jpeg';
                    break;
                case 'png':
                    $type = 'image/png';
                    break;
                default:
                    $type = 'unknown';
                    break;
            }

            if ($type == 'unknown') {
                throw new \Exception('Unknown type.');
            }

            $headers = array(
                'Content-Type' => $type,
                'UnityReports' => 'OK',
                'ClaroBI' => 'OK'
            );

            return new Response($content, 200, $headers);
        } catch (\Exception $exception) {
            return new Response(self::ERR . $exception->getMessage());
        }
    }
}

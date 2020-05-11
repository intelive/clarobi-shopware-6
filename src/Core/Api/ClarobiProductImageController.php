<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Content\Media\Thumbnail\ThumbnailService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnail\MediaThumbnailEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Shopware\Core\Content\Media\Aggregate\MediaDefaultFolder\MediaDefaultFolderEntity;
use Shopware\Core\Content\Media\Aggregate\MediaThumbnailSize\MediaThumbnailSizeEntity;

/**
 * Class ClarobiProductImageController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiProductImageController extends AbstractController
{
    /** @var ThumbnailService */
    protected $thumbnailService;
    /** @var EntityRepositoryInterface */
    protected $productRepository;
    /** @var EntityRepositoryInterface */
    protected $mediaThumbnailSizeRepository;
    /** @var EntityRepositoryInterface */
    protected $mediaDefaultFolderRepository;
    /** @var EntityRepositoryInterface */
    protected $mediaConfigThumbnailSizeRepo;

    const ERR = 'ERROR: ';
    const WIDTH = 50;

    /**
     * ClarobiProductImageController constructor.
     */
    public function __construct(
        ThumbnailService $thumbnailService,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $mediaThumbnailSizeRepository,
        EntityRepositoryInterface $mediaDefaultFolderRepository,
        EntityRepositoryInterface $mediaConfigThumbnailSizeRepo
    )
    {
        $this->thumbnailService = $thumbnailService;
        $this->productRepository = $productRepository;
        $this->mediaThumbnailSizeRepository = $mediaThumbnailSizeRepository;
        $this->mediaDefaultFolderRepository = $mediaDefaultFolderRepository;
        $this->mediaConfigThumbnailSizeRepo = $mediaConfigThumbnailSizeRepo;
    }

    /**
     * @Route("/clarobi/product/get-image/id/{id}", name="clarobi.product.get.image")
     *
     * @param Request $request
     * @return Response
     */
    public function getImage(Request $request): Response
    {
        try {
            $id = (int)$request->get('id');

            // Insert thumbnail size if not found
            $this->insertThumbnailSize(self::WIDTH);

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
            $minWidth = 1920;

            // Get media
            $mediaElements = $product->getMedia()->getMedia()->getElements();
            foreach ($mediaElements as $media) {
                // Update thumbnails
//                $resultUpdate = $this->thumbnailService->updateThumbnails($media, Context::createDefaultContext());
                $resultGenerate = $this->thumbnailService->generateThumbnails($media, Context::createDefaultContext());

//                ($resultUpdate > 0 ? var_dump('updated') : '');
//                ($resultGenerate > 0 ? var_dump('generated') : '');

                /** @var MediaThumbnailEntity $thumbnail */
                foreach ($media->getThumbnails() as $thumbnail) {
//                    var_dump($thumbnail->getUrl());

                    $thumbnailWidth = $thumbnail->getWidth();
                    if ($thumbnailWidth <= $minWidth) {
                        $minWidth = $thumbnailWidth;
                        $path = $thumbnail->getUrl();
                    }
                }
            }

            // Check if any path was found
            if (!$path) {
                throw new \Exception('No image found for this product.');
            }
            // Get file content
            $content = file_get_contents($path);
            if (!$content) {
                throw new \Exception('Could not load image.');
            }
            // Get file extension
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
                'ClaroBI' => 'OK',
                'MinWidthFound' => $minWidth,
                'DefaultImageUrl' => $path
            );

            return new Response($content, 200, $headers);
        } catch (\Exception $exception) {
            return new Response(self::ERR . $exception->getMessage());
        }
    }

    /**
     * @param $thumbnailSize
     * @return bool
     */
    private function insertThumbnailSize($thumbnailSize)
    {
        // add size in media_thumbnail_size
        $mediaCriteria = new Criteria();
        $mediaCriteria->addFilter(new EqualsFilter('width', $thumbnailSize));
        $mediaSizeColl = $this->mediaThumbnailSizeRepository->search(
            $mediaCriteria,
            Context::createDefaultContext()
        )->getEntities();
        if (!$mediaSizeColl->count()) {
            $this->mediaThumbnailSizeRepository->upsert(
                [
                    ['width' => $thumbnailSize, 'height' => $thumbnailSize],
                ],
                Context::createDefaultContext());
        }

        /** @var MediaThumbnailSizeEntity $mediaThumbnailSizeColl */
        $newMediaThumbnailSize = $this->mediaThumbnailSizeRepository->search(
            $mediaCriteria,
            Context::createDefaultContext()
        )->first();

        if ($newMediaThumbnailSize) {
            // Get default folder
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('entity', 'product'))
                ->addAssociation('folder.configuration');

            /** @var MediaDefaultFolderEntity $mediaDefaultFolderEntity */
            $mediaDefaultFolder = $this->mediaDefaultFolderRepository->search(
                $criteria, Context::createDefaultContext()
            )->first();

            /** @var MediaFolderEntity $mediaFolder */
            $mediaFolder = $mediaDefaultFolder->getFolder();
            if ($mediaFolder) {
                $mediaFolderConfigId = $mediaFolder->getConfigurationId();
                $this->mediaConfigThumbnailSizeRepo->upsert(
                    [
                        [
                            'mediaFolderConfigurationId' => $mediaFolderConfigId,
                            'mediaThumbnailSizeId' => $newMediaThumbnailSize->getId()
                        ]
                    ],
                    Context::createDefaultContext()
                );
            }
        }
    }
}

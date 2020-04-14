<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Context;

/**
 * Class ClarobiProductController
 *
 * @RouteScope(scopes={"storefront"})
 * @package Clarobi\Core\Api
 */
class ClarobiProductController extends ClarobiAbstractController
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var EncodeResponseService
     */
    protected $encodeResponse;

    /**
     * @var ClarobiConfigService
     */
    protected $configService;

    const ENTITY_NAME = 'product';

    const IGNORED_KEYS = [
//        'parentId',
//        'autoIncrement',
//        'active',
//        'productNumber',
//        'stock',
//        'availableStock',
//        'available',
//        'name',
//        'variantRestrictions',
//        'properties',
//        'options',
//        'visibilities',
//        'createdAt',
//        'updatedAt',
//        'id',
//        'optionIds',
        'propertyIds',
//        'price',
//        'parent',


        'categories',
        'categoryTree',
        'childCount',
        'taxId',
        'manufacturerId',
        'unitId',
        'displayGroup',
        'manufacturerNumber',
        'ean',
        'deliveryTimeId',
        'deliveryTime',
        'restockTime',
        'isCloseout',
        'purchaseSteps',
        'maxPurchase',
        'minPurchase',
        'purchaseUnit',
        'referenceUnit',
        'shippingFree',
        'purchasePrice',
        'markAsTopseller',
        'weight',
        'width',
        'height',
        'length',
        'releaseDate',
        'keywords',
        'description',
        'metaDescription',
        'metaTitle',
        'packUnit',
        'configuratorGroupConfig',
        'tax',
        'manufacturer',
        'unit',
        'prices',
        'listingPrices',
        'cover',
        'children',
        'media',
        'searchKeywords',
        'translations',
        'tags',
        'configuratorSettings',
        'categoriesRo',
        'coverId',
        'blacklistIds',
        'whitelistIds',
        'customFields',
        'tagIds',
        'productReviews',
        'ratingAverage',
        'mainCategories',
        'seoUrls',
        'orderLineItems',
        'crossSellings',
        'crossSellingAssignedProducts',
        '_uniqueIdentifier',
        'versionId',
        'translated',
        'extensions',
        'parentVersionId',
        'productManufacturerVersionId',
        'productMediaVersionId'
    ];

    /**
     * ClarobiProductController constructor.
     *
     * @param EntityRepositoryInterface $productRepository
     * @param ClarobiConfigService $configService
     * @param EncodeResponseService $encodeResponse
     */
    public function __construct(
        EntityRepositoryInterface $productRepository,
        ClarobiConfigService $configService,
        EncodeResponseService $responseService
    )
    {
        $this->productRepository = $productRepository;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
    }

    /**
     * @Route("/clarobi/product", name="clarobi.product.list")
     * @param Request $request
     * @return Response
     */
    public function listAction(Request $request): Response
    {
        try {
            // Verify request
            $this->verifyRequest($request, $this->configService->getConfigs());
            // Get param
            $from_id = $request->get('from_id');

            $from_id = $this->decToHex($from_id);
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }

        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(10)
            ->addFilter(new RangeFilter('id',['gte'=>$from_id]))
            ->addSorting(new FieldSorting('id', FieldSorting::ASCENDING))
            ->addAssociation('parent')
            ->addAssociation('options.group')
            ->addAssociation('property');

        /**
         * @todo resolve - not working
         * $criteria->setIncludes(['name', 'autoIncrement']);
         */

        /** @var EntityCollection $entities */
        $entities = $this->productRepository->search($criteria, $context);

        $mappedEntities = [];
        /** @var ProductEntity $element */
        foreach ($entities->getElements() as $element) {
            // map by ignoring keys
            $mappedEntities[] = $this->mapProductEntity($element->jsonSerialize());

        }

        return new JsonResponse($mappedEntities);
//        return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::ENTITY_NAME));
    }

    /**
     * @param array $product
     * @return array
     */
    private function mapProductEntity($product)
    {
        $mappedKeys['entity_name'] = self::ENTITY_NAME;
        foreach ($product as $key => $value) {
            if (in_array($key, self::IGNORED_KEYS)) {
                continue;
            }
            $mappedKeys[$key] = $value;
        }
        $mappedKeys['id'] = $this->hexToDec($product['id']);
        $mappedKeys['type'] = ($mappedKeys['parentId'] ? 'configurable' : 'simple');

//        if ($mappedKeys['parentId']) {
//            $prodColl = new ProductCollection();
//            $productParent = $prodColl->filterByParentId($mappedKeys['parentId']);
//            var_dump($productParent);
//            die;
//        }

        /**
         * @todo get sku
         * @todo get parent auto_increment
         * @todo get options/variations
         */

        return $mappedKeys;
    }
}

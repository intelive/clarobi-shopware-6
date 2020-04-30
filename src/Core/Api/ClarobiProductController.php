<?php declare(strict_types=1);

namespace Clarobi\Core\Api;

use Shopware\Core\Framework\Context;
use Clarobi\Utils\ProductMapperHelper;
use Clarobi\Service\ClarobiConfigService;
use Clarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Clarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

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

    /**
     * @var ProductMapperHelper
     */
    protected $mapperHelper;

    const ENTITY_NAME = 'product';

    const IGNORED_KEYS = [
//        'autoIncrement', 'active', 'productNumber', 'stock', 'availableStock', 'available', 'name',
//        'visibilities', 'createdAt', 'updatedAt', 'id', 'price', 'childCount',
        'parentId', 'parent',
        'optionIds', 'propertyIds',
        'properties',
        'options',
        'children',
        'variantRestrictions',
        'categories',
        'categoryTree', 'taxId', 'manufacturerId', 'unitId', 'displayGroup', 'media',
        'manufacturerNumber', 'ean', 'deliveryTimeId', 'deliveryTime', 'restockTime', 'isCloseout', 'purchaseSteps',
        'maxPurchase', 'minPurchase', 'purchaseUnit', 'referenceUnit', 'shippingFree', 'purchasePrice',
        'markAsTopseller', 'weight', 'width', 'height', 'length', 'releaseDate', 'keywords', 'description',
        'metaDescription', 'metaTitle', 'packUnit', 'configuratorGroupConfig', 'tax', 'manufacturer', 'unit', 'prices',
        'listingPrices', 'cover', 'searchKeywords', 'translations', 'tags', 'configuratorSettings',
        'categoriesRo', 'coverId', 'blacklistIds', 'whitelistIds', 'customFields', 'tagIds', 'productReviews',
        'ratingAverage', 'mainCategories', 'seoUrls', 'orderLineItems', 'crossSellings', 'crossSellingAssignedProducts',
        '_uniqueIdentifier', 'versionId', 'translated', 'extensions', 'parentVersionId', 'productManufacturerVersionId',
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
        EncodeResponseService $responseService,
        ProductMapperHelper $mapperHelper
    )
    {
        $this->productRepository = $productRepository;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
        $this->mapperHelper = $mapperHelper;
    }

    /**
     * @Route("/clarobi/product", name="clarobi.product.list")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request): JsonResponse
    {
        try {
            // Verify request
            $this->verifyParam($request);
            $this->verifyToken($request, $this->configService->getConfigs());
            // Get param
            $from_id = $request->get('from_id');

            $context = Context::createDefaultContext();
            $criteria = new Criteria();
            $criteria->setLimit(50)
                ->addFilter(new RangeFilter('autoIncrement', ['gt' => $from_id]))
                ->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING))
                ->addAssociation('options.group.translations')
                ->addAssociation('properties.group.translations')
                ->addAssociation('children.options.group.translations')
//                ->addAssociation('children.properties.group')
//                ->addAssociation('parent')
            ;

            /** @var EntityCollection $entities */
            $entities = $this->productRepository->search($criteria, $context);

            $mappedEntities = [];
            $lastId = 0;
            if($entities->getElements()){
                /** @var ProductEntity $element */
                foreach ($entities->getElements() as $element) {
                    // map by ignoring keys
                    $mappedEntities[] = $this->mapProductEntity($element->jsonSerialize());
                }
                $lastId = $element->getAutoIncrement();
            }

            return new JsonResponse($this->encodeResponse->encodeResponse(
                $mappedEntities,
                self::ENTITY_NAME,
                $lastId
            ));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * @param $product
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
        $mappedKeys['type'] = ($product['childCount'] ? 'configurable' : 'simple');

        if ($product['parentId']) {
            $criteria = new Criteria([$product['parentId']]);
            /** @var ProductEntity $parentProduct */
            $parentProduct = $this->productRepository->search($criteria, Context::createDefaultContext())->first();
            $mappedKeys['parentAutoIncrement'] = $parentProduct->getAutoIncrement();
        } else {
            $mappedKeys['parentAutoIncrement'] = null;
        }

        $options = $this->mapperHelper->getProductOptions($product);
        $properties = $this->mapperHelper->mapOptionCollection($product['properties']);

//        $mappedKeys['options'] = $options;
//        $mappedKeys['properties'] = $this->mapperHelper->propertiesToMultiValues($properties);

        $mappedKeys['options'] = $this->mapperHelper->mergeOptionsAndProperties($options, $properties);

        return $mappedKeys;
    }
}

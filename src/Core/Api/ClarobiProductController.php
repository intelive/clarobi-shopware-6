<?php declare(strict_types=1);

namespace ClarobiClarobi\Core\Api;

use Shopware\Core\Framework\Context;
use ClarobiClarobi\Utils\ProductMapperHelper;
use ClarobiClarobi\Service\ClarobiConfigService;
use ClarobiClarobi\Service\EncodeResponseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Content\Product\ProductEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use ClarobiClarobi\Core\Framework\Controller\ClarobiAbstractController;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Class ClarobiProductController
 *
 * @package ClarobiClarobi\Core\Api
 */
class ClarobiProductController extends ClarobiAbstractController
{
    /** @var EntityRepositoryInterface $productRepository */
    protected $productRepository;
    /** @var EncodeResponseService $encodeResponse */
    protected $encodeResponse;
    /** @var ClarobiConfigService $configService */
    protected $configService;
    /** @var ProductMapperHelper $mapperHelper */
    protected $mapperHelper;

    protected static $entityName = 'product';
    protected static $ignoreKeys = [
        'parentId', 'parent', 'optionIds', 'propertyIds', 'properties', 'options', 'children', 'variantRestrictions',
        'categories', 'categoryTree', 'taxId', 'manufacturerId', 'unitId', 'displayGroup', 'media',
        'manufacturerNumber', 'ean', 'deliveryTimeId', 'deliveryTime', 'restockTime', 'isCloseout', 'purchaseSteps',
        'maxPurchase', 'minPurchase', 'purchaseUnit', 'referenceUnit', 'shippingFree', 'purchasePrice',
        'markAsTopseller', 'weight', 'width', 'height', 'length', 'releaseDate', 'keywords', 'description',
        'metaDescription', 'metaTitle', 'packUnit', 'configuratorGroupConfig', 'tax', 'manufacturer', 'unit', 'prices',
        'listingPrices', 'cover', 'searchKeywords', 'translations', 'tags', 'configuratorSettings', 'categoriesRo',
        'coverId', 'blacklistIds', 'whitelistIds', 'customFields', 'tagIds', 'productReviews', 'ratingAverage',
        'mainCategories', 'seoUrls', 'orderLineItems', 'crossSellings', 'crossSellingAssignedProducts',
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
    public function __construct(EntityRepositoryInterface $productRepository, ClarobiConfigService $configService,
                                EncodeResponseService $responseService, ProductMapperHelper $mapperHelper
    )
    {
        $this->productRepository = $productRepository;
        $this->configService = $configService;
        $this->encodeResponse = $responseService;
        $this->mapperHelper = $mapperHelper;
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route(path="/clarobi/product", name="clarobi.product.list", methods={"GET"})
     */
    public function listAction(Request $request)
    {
        try {
            $this->verifyParam($request);
            $this->verifyToken($request, $this->configService->getConfigs());
            $from_id = $request->get('from_id');

            $context = Context::createDefaultContext();
            $criteria = new Criteria();
            $criteria->setLimit(50)
                ->addFilter(new RangeFilter('autoIncrement', ['gt' => $from_id]))
                ->addSorting(new FieldSorting('autoIncrement', FieldSorting::ASCENDING))
                ->addAssociation('options.group.translations')
                ->addAssociation('properties.group.translations')
                ->addAssociation('children.options.group.translations');

            /** @var EntityCollection $entities */
            $entities = $this->productRepository->search($criteria, $context);

            $mappedEntities = [];
            $lastId = 0;
            if ($entities->getElements()) {
                /** @var ProductEntity $element */
                foreach ($entities->getElements() as $element) {
                    $mappedEntities[] = $this->mapProductEntity($element->jsonSerialize());
                }
                $lastId = $element->getAutoIncrement();
            }

            return new JsonResponse($this->encodeResponse->encodeResponse($mappedEntities, self::$entityName, $lastId));
        } catch (\Exception $exception) {
            return new JsonResponse(['status' => 'error', 'message' => $exception->getMessage()]);
        }
    }

    /**
     * Map product entity.
     *
     * @param $product
     * @return array
     */
    private function mapProductEntity($product)
    {
        $mappedKeys = $this->ignoreEntityKeys($product, self::$entityName, self::$ignoreKeys);

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
        $mappedKeys['options'] = $this->mapperHelper->mergeOptionsAndProperties($options, $properties);

        return $mappedKeys;
    }
}

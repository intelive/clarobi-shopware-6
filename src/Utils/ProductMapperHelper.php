<?php declare(strict_types=1);

namespace ClarobiClarobi\Utils;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionEntity;
use Shopware\Core\Content\Property\Aggregate\PropertyGroupOption\PropertyGroupOptionCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Class ProductMapperHelper
 *
 * @package ClarobiClarobi\Utils
 */
class ProductMapperHelper
{
    /** @var EntityRepository $productRepository */
    protected $productRepository;

    /**
     * ProductMapperHelper constructor.
     *
     * @param EntityRepositoryInterface $productRepository
     */
    public function __construct(EntityRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param $options
     * @param array $properties
     * @return array
     */
    public function mergeOptionsAndProperties($options, $properties)
    {
        $mergedArray = array_merge($options, $properties);
        $mapped = [];
        foreach ($mergedArray as $item) {
            if (!key_exists($item['label'], $mapped)) {
                $mapped[$item['label']] = [
                    'value' => $item['value'],
                    'attribute_id' => $item['attribute_id']
                ];
            } else {
                $oldValues = explode(', ', $mapped[$item['label']]['value']);
                $oldValues[] = $item['value'];
                $mapped[$item['label']]['value'] = implode(', ', $oldValues);
            }
        }

        return $mapped;
    }

    /**
     * @param $product
     * @return array
     */
    public function getProductOptions($product)
    {
        $optionsArray = [];
        // If product is simple - get options
        if (!$product['childCount']) {
            $optionsArray = $this->mapOptionCollection($product['options']);
        } else {
            // For each children - get options
            /** @var ProductCollection $children */
            $children = $product['children'];
            foreach ($children->getElements() as $element) {
                $elementOptions = $this->mapOptionCollection($element->getOptions());
                $optionsArray = array_merge($optionsArray, $elementOptions);
            }
        }

        return array_unique($optionsArray, SORT_REGULAR);
    }

    /**
     * @param PropertyGroupOptionCollection $options
     * @return array
     */
    public function mapOptionCollection(PropertyGroupOptionCollection $options)
    {
        $mappedOptions = [];

        /** @var PropertyGroupOptionEntity $option */
        foreach ($options as $option) {
            $property_group_id = $option->getGroupId();
            $groupLangId = $option->getGroup()->getTranslations()->first()->getLanguageId();

            $attr_id = $property_group_id . $groupLangId;
            $mappedOptions[] = [
                'value' => $option->getName(),
                'label' => $option->getGroup()->getName(),
                'attribute_id' => $attr_id
            ];
        }

        return $mappedOptions;
    }

    /**
     * @param OrderEntity $order
     * @param Context $context
     * @return array
     */
    public function mapOrderLineItems($order, $context)
    {
        $lineItems = [];

        /** @var OrderLineItemEntity $lineItem */
        foreach ($order['lineItems'] as $lineItem) {
            $item = $lineItem->jsonSerialize();
            if ($lineItem->getType() == 'product') {
                /** @var ProductEntity $product */
                $product = $item['product'];

                // Unset product to manage less data
                unset($item['product']);

                $options = $this->mapOptionCollection($product->getOptions());
                $properties = $this->mapOptionCollection($product->getProperties());

                $parentAutoIncrement = $parentProductNumber = null;
                if ($product->getParentId()) {
                    $criteria = new Criteria([$product->getParentId()]);
                    /** @var ProductEntity $parentProduct */
                    $parentProduct = $this->productRepository->search($criteria, $context)->first();
                    $parentAutoIncrement = $parentProduct->getAutoIncrement();
                    $parentProductNumber = $parentProduct->getProductNumber();
                }
                $item['product'] = [
                    'autoIncrement' => $product->getAutoIncrement(),
                    'productNumber' => $product->getProductNumber(),
                    'childCount' => $product->getChildCount(),
                    'categories' => ($product->getCategories()->first() ?
                        $product->getCategories()->first()->getBreadcrumb()
                        : []
                    ),
                    'parent' => [
                        'autoIncrement' => $parentAutoIncrement,
                        'productNumber' => $parentProductNumber
                    ],
                    'options' => $this->mergeOptionsAndProperties($options, $properties)
                ];
                $lineItems[] = $item;
            }
        }

        return $lineItems;
    }
}

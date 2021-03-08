<?php declare(strict_types=1);

namespace ClarobiClarobi\Utils;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Event\LineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\LineItemRemovedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Checkout\Cart\Event\LineItemQuantityChangedEvent;

/**
 * Class ProductCountsDataExtractor
 *
 * @package ClarobiClarobi\Utils
 */
class ProductCountsDataExtractor
{
    /** @var EntityRepository $entityRespository */
    protected $entityRepository;

    /**
     * ProductCountsDataExtractor constructor.
     *
     * @param EntityRepository $entityRepository
     */
    public function __construct(EntityRepository $entityRepository)
    {
        $this->entityRepository = $entityRepository;
    }

    /**
     * @param LineItemAddedEvent|LineItemQuantityChangedEvent|LineItemRemovedEvent $event
     * @param bool $changedQuantity
     * @return array
     */
    public function getLineItemDetails($event, $changedQuantity = false)
    {
        $itemType = $event->getLineItem()->getType();

        if ($itemType === LineItem::PRODUCT_LINE_ITEM_TYPE) {
            $itemId = $event->getLineItem()->getId();
            $itemQty = $event->getLineItem()->getQuantity();
            $itemAutoIncrement = $this->getProductAutoIncrement($itemId, $event->getContext());
            $oldQuantity = null;
            if ($changedQuantity) {
                $oldQuantity = $event->getLineItem()->getPriceDefinition()->getQuantity();
            }
            return [
                'id' => $itemId,
                'auto_increment' => $itemAutoIncrement,
                'quantity' => $itemQty,
                'oldQuantity' => $oldQuantity
            ];
        }
        return [];
    }

    /**
     * Get auto_increment value from product hex_id.
     *
     * @param $itemId
     * @param SalesChannelContext $context
     * @return int
     */
    protected function getProductAutoIncrement($itemId, $context)
    {
        $itemAutoIncrement = 0;

        $context = $context->getContext(); // Get base context from SalesChannelContext
        $criteria = new Criteria([$itemId]);
        $criteria->setLimit(1);
        $productColl = $this->entityRepository->search($criteria, $context)->getEntities();

        /** @var ProductEntity $item */
        foreach ($productColl as $item) {
            $itemAutoIncrement = $item->getAutoIncrement();
            break;
        }
        return $itemAutoIncrement;
    }
}

<?php declare(strict_types=1);

namespace Clarobi\Utils;

use Shopware\Core\Checkout\Cart\Event\LineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\LineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\Event\LineItemRemovedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;

class ProductCountsDataExtractor
{
    /**
     * @var EntityRepository
     */
    protected $entityRespository;

    public function __construct(EntityRepository $entityRepository)
    {
        $this->entityRespository = $entityRepository;
    }

    /**
     * @param LineItemAddedEvent|LineItemQuantityChangedEvent|LineItemRemovedEvent $event
     * @return array
     */
    public function getLineItemDetails($event)
    {
        $itemType = $event->getLineItem()->getType();

        $itemId = $itemAutoIncrement = $itemQty = 0;
        if ($itemType === LineItem::PRODUCT_LINE_ITEM_TYPE) {
            $itemId = $event->getLineItem()->getId();
            $itemQty = $event->getLineItem()->getQuantity();
            $itemAutoIncrement = $this->getProductAutoIncrement($itemId);
        }

        return [
            'id' => $itemId,
            'auto_increment' => $itemAutoIncrement,
            'quantity' => $itemQty
        ];
    }

    /**
     * Get auto_increment value from product hex_id.
     *
     * @param $itemId
     * @return int
     */
    protected function getProductAutoIncrement($itemId)
    {
        $itemAutoIncrement = 0;

        $context = Context::createDefaultContext();
        $criteria = new Criteria([$itemId]);
        $criteria->setLimit(1);
        $productColl = $this->entityRespository->search($criteria, $context)->getEntities();
        /** @var ProductEntity $item */
        foreach ($productColl as $item) {
            $itemAutoIncrement = $item->getAutoIncrement();
            break;
        }

        return $itemAutoIncrement;
    }
}

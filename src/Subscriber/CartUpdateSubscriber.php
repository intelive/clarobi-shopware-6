<?php declare(strict_types=1);

namespace Clarobi\Subscriber;

use Clarobi\Utils\ProductCountsDataExtractor;
use Clarobi\Utils\ProductCountsDataUpdate;
use Shopware\Core\Checkout\Cart\Event\LineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\LineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\Event\LineItemRemovedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartUpdateSubscriber implements EventSubscriberInterface
{
    /**
     * @var ProductCountsDataUpdate
     */
    protected $dataUpdate;

    /**
     * @var ProductCountsDataExtractor
     */
    protected $dataExtractor;

    public function __construct(ProductCountsDataUpdate $dataUpdate, ProductCountsDataExtractor $dataExtractor)
    {
        $this->dataUpdate = $dataUpdate;
        $this->dataExtractor = $dataExtractor;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            LineItemAddedEvent::class => 'onLineItemAdded',
            LineItemQuantityChangedEvent::class => 'onLineItemQuantityChanged',
            LineItemRemovedEvent::class => 'onLineItemRemoved',
        ];
    }

    /**
     * This function is called whenever a new line item has been
     * added to the cart from within the controllers.
     *
     * @param LineItemAddedEvent $event
     */
    public function onLineItemAdded(LineItemAddedEvent $event): void
    {
        $valuesToInsert = $this->dataExtractor->getLineItemDetails($event);
        $this->dataUpdate->updateProductCountersTable(
            $valuesToInsert['id'],
            $valuesToInsert['auto_increment'],
            $valuesToInsert['quantity'],
            'adds_to_cart',
            '+'
        );
    }

    /**
     * This function is called whenever a line item quantity changes.
     *
     * @param LineItemQuantityChangedEvent $event
     */
    public function onLineItemQuantityChanged(LineItemQuantityChangedEvent $event): void
    {
        /**
         * @todo find if quantity increases or decreases
         */
//        $valuesToInsert = $this->dataExtractor->getLineItemDetails($event);
//        $this->dataUpdate->updateProductCountersTable(
//            $valuesToInsert['id'],
//            $valuesToInsert['auto_increment'],
//            $valuesToInsert['quantity'],
//            'adds_to_cart',
//            '-'
//        );
    }

    /**
     * This function is called whenever a line item is being removed
     * from the cart from within a controller.
     *
     * @param LineItemRemovedEvent $event
     */
    public function onLineItemRemoved(LineItemRemovedEvent $event): void
    {
        $valuesToInsert = $this->dataExtractor->getLineItemDetails($event);
        $this->dataUpdate->updateProductCountersTable(
            $valuesToInsert['id'],
            $valuesToInsert['auto_increment'],
            $valuesToInsert['quantity'],
            'adds_to_cart',
            '-'
        );
    }
}

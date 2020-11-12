<?php declare(strict_types=1);

namespace ClarobiClarobi\Subscriber;

use ClarobiClarobi\Utils\ProductCountsDataUpdate;
use ClarobiClarobi\Utils\ProductCountsDataExtractor;
use Shopware\Core\Checkout\Cart\Event\LineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\LineItemRemovedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\Event\LineItemQuantityChangedEvent;

/**
 * Class CartUpdateSubscriber
 *
 * @package ClarobiClarobi\Subscriber
 */
class CartUpdateSubscriber implements EventSubscriberInterface
{
    /** @var ProductCountsDataUpdate $dataUpdate */
    protected $dataUpdate;
    /** @var ProductCountsDataExtractor $dataExtractor */
    protected $dataExtractor;

    /**
     * CartUpdateSubscriber constructor.
     *
     * @param ProductCountsDataUpdate $dataUpdate
     * @param ProductCountsDataExtractor $dataExtractor
     */
    public function __construct(ProductCountsDataUpdate $dataUpdate, ProductCountsDataExtractor $dataExtractor)
    {
        $this->dataUpdate = $dataUpdate;
        $this->dataExtractor = $dataExtractor;
    }

    /**
     * Register events.
     *
     * @return array
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
        if (!empty($valuesToInsert)) {
            $this->dataUpdate->updateProductCountersTable(
                $valuesToInsert['id'],
                $valuesToInsert['auto_increment'],
                $valuesToInsert['quantity'],
                ProductCountsDataUpdate::$itemAddToCart,
                ProductCountsDataUpdate::$itemAddition
            );
        }
    }

    /**
     * This function is called whenever a line item quantity changes.
     *
     * @param LineItemQuantityChangedEvent $event
     */
    public function onLineItemQuantityChanged(LineItemQuantityChangedEvent $event)
    {
        $valuesToInsert = $this->dataExtractor->getLineItemDetails($event, true);
        if (!empty($valuesToInsert)) {
            if ($valuesToInsert['oldQuantity'] < $valuesToInsert['quantity']) {
                $operation = ProductCountsDataUpdate::$itemSubtraction;
                $quantity = $valuesToInsert['quantity'];
            } else {
                $operation = ProductCountsDataUpdate::$itemAddition;
                $quantity = $valuesToInsert['quantity'] - $valuesToInsert['oldQuantity'];
            }
            $this->dataUpdate->updateProductCountersTable(
                $valuesToInsert['id'],
                $valuesToInsert['auto_increment'],
                $quantity,
                ProductCountsDataUpdate::$itemAddToCart,
                $operation
            );
        }
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
        if (!empty($valuesToInsert)) {
            $this->dataUpdate->updateProductCountersTable(
                $valuesToInsert['id'],
                $valuesToInsert['auto_increment'],
                $valuesToInsert['quantity'],
                ProductCountsDataUpdate::$itemAddToCart,
                ProductCountsDataUpdate::$itemSubtraction
            );
        }
    }
}

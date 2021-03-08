<?php declare(strict_types=1);

namespace ClarobiClarobi\Subscriber;

use ClarobiClarobi\Utils\ProductCountsDataUpdate;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProductPageLoadSubscriber
 *
 * @package ClarobiClarobi\Subscriber
 */
class ProductPageLoadSubscriber implements EventSubscriberInterface
{
    /** @var ProductCountsDataUpdate $dataUpdate */
    protected $dataUpdate;

    /**
     * ProductPageLoadSubscriber constructor.
     *
     * @param ProductCountsDataUpdate $dataUpdate
     */
    public function __construct(ProductCountsDataUpdate $dataUpdate)
    {
        $this->dataUpdate = $dataUpdate;
    }

    /**
     * Register events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ProductPageLoadedEvent::class => 'onProductProductPageLoaded',
        ];
    }

    /**
     * @param ProductPageLoadedEvent $event
     */
    public function onProductProductPageLoaded(ProductPageLoadedEvent $event): void 
    {
        /** @var ProductPage $page */
        $page = $event->getPage();
        $product = $page->getProduct();
        $this->dataUpdate->updateProductCountersTable(
            $product->getId(),
            $product->getAutoIncrement(),
            1,
            ProductCountsDataUpdate::$itemView
        );
    }
}

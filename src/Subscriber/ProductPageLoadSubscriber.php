<?php declare(strict_types=1);

namespace Clarobi\Subscriber;

use Clarobi\Utils\ProductCountsDataUpdate;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProductPageLoadSubscriber
 * @package Clarobi\Subscriber
 */
class ProductPageLoadSubscriber implements EventSubscriberInterface
{
    /**
     * @var ProductCountsDataUpdate
     */
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
     * @inheritDoc
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
            'views'
        );
    }
}

<?php declare(strict_types=1);

namespace ClarobiClarobi\Subscriber;

use ClarobiClarobi\Utils\AutoIncrementHelper;
use Shopware\Core\Checkout\Cart\Event\CartSavedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CartCreateSubscriber
 *
 * @package ClarobiClarobi\Subscriber
 * @author Georgiana Camelia Gitan (g.gitan@interlive.ro)
 */
class CartCreateSubscriber implements EventSubscriberInterface
{
    /** @var AutoIncrementHelper $helper */
    protected $helper;

    public function __construct(AutoIncrementHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Register events.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        // todo: CartSavedEvent @deprecated tag:v6.4.0 - Will implement Shopware\Core\Framework\Event\ShopwareSalesChannelEvent
        return [
            CartSavedEvent::class => 'onCartSaved',
        ];
    }

    /**
     * This function is called whenever a new cart is created for assigning an auto_increment id.
     *
     * @param CartSavedEvent $event
     */
    public function onCartSaved(CartSavedEvent $event): void
    {
        $cartToken = $event->getCart()->getToken();
        $this->helper->createAutoIncrementForEntity($this->helper::ENTITY_TYPE_CART, $cartToken);
    }
}

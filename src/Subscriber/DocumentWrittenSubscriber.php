<?php declare(strict_types=1);

namespace ClarobiClarobi\Subscriber;

use ClarobiClarobi\Utils\AutoIncrementHelper;
use Shopware\Core\Checkout\Document\DocumentGenerator\CreditNoteGenerator;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DocumentWrittenSubscriber
 *
 * @package ClarobiClarobi\Subscriber
 */
class DocumentWrittenSubscriber implements EventSubscriberInterface
{
    /** @var AutoIncrementHelper $helper */
    protected $helper;

    protected static $entityDocument = 'document';
    protected static $entityEvent = 'document.written';
    protected static $documentTypeInvoice = InvoiceGenerator::INVOICE;
    protected static $documentTypeCreditNote = CreditNoteGenerator::CREDIT_NOTE;

    /**
     * DocumentWrittenSubscriber constructor.
     *
     * @param AutoIncrementHelper $helper
     */
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
        return [
            'document.written' => 'onEntityWrittenEvent',
        ];
    }

    /**
     * This function is called whenever a new document is created for assigning an auto_increment id.
     *
     * @param EntityWrittenEvent $event
     * @param $eventName
     */
    public function onEntityWrittenEvent(EntityWrittenEvent $event, $eventName): void
    {
        $entityName = $event->getEntityName();
        $createdDocuments = [];
        if ($entityName === self::$entityDocument && $eventName === self::$entityEvent) {
            // Get all documents data
            $data = $event->getPayloads();
            foreach ($data as $datum) {
                $type = $datum['config']['name'];
                if (in_array($type, [self::$documentTypeInvoice, self::$documentTypeCreditNote])) {
                    $createdDocuments[] = ['id' => $datum['id'], 'name' => $type];
                }
            }
            if (!empty($createdDocuments)) {
                $this->helper->createAutoIncrementForEntity($this->helper::ENTITY_TYPE_DOCUMENT, $createdDocuments);
            }
        }
    }
}

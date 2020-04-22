<?php declare(strict_types=1);

namespace Clarobi\Extension\Document;

use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtensionInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ObjectField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class DocumentExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new IntField(
                'clarobi_auto_increment',
                'clarobiAutoIncrement'
            ))
                ->addFlags(new Runtime())
        );
        /**
         * ->addFlags(new Inherited())
         * Return error that only fields flagged as Runtime can be added as extension,
         * or fields of type association, fk fields and many-to-one relations.
         */
    }

    /**
     * @inheritDoc
     */
    public function getDefinitionClass(): string
    {
        return DocumentDefinition::class;
    }
}

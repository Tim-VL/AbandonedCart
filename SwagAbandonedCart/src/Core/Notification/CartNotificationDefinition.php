<?php declare(strict_types=1);

namespace Swag\Abandoned\Core\Notification;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;


class CartNotificationDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'wk_abandoned_cart_notification';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CartNotificationEntity::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
            (new BlobField('cart_items', 'cartItems'))->addFlags(new Required()),
            (new DateTimeField('last_notified', 'lastNotified'))->addFlags(new Required()),
            (new DateTimeField('next_notified', 'nextNotified')),
            (new IntField('times_notified', 'timesNotified')),
            (new IntField('times_handler_notified', 'timesHandlerNotified')),
            (new OneToOneAssociationField('customer', 'customer_id', 'id', CustomerDefinition::class))
        ]);
    }
}
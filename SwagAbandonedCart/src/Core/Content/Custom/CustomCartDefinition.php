<?php declare(strict_types=1);

namespace Swag\Abandoned\Core\Content\Custom;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Checkout\Shipping\ShippingMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Currency\CurrencyDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;

class CustomCartDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'cart';

    public $timestamps = false;

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CustomCartEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CustomCartCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        $currentVersion = \Composer\InstalledVersions::getVersionRanges("shopware/core");
        $targetVersion = '6.4.12.0';
        $dataField = 'payload';
        
        if(\version_compare($currentVersion,  $targetVersion, '>') ){
            $dataField = 'cart';
        }
       

        return new FieldCollection([
            (new StringField('token', 'token'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField($dataField, $dataField))
        ]);
    }

   
}
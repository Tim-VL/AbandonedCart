<?php

namespace Swag\Abandoned\Core\Content\CustomCart;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(CustomCartEntity $entity)
 * @method void               set(string $key, CustomCartEntity $entity)
 * @method CustomCartEntity[]    getIterator()
 * @method CustomCartEntity[]    getElements()
 * @method CustomCartEntity|null get(string $key)
 * @method CustomCartEntity|null first()
 * @method CustomCartEntity|null last()
 */
class CustomCartCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CustomCartEntity::class;
    }
}
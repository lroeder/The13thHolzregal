<?php declare(strict_types=1);

namespace The13thHolzregal\Core\Content\HolzregalEntity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(HolzregalEntity $entity)
 * @method void              set(string $key, HolzregalEntity $entity)
 * @method HolzregalEntity[]    getIterator()
 * @method HolzregalEntity[]    getElements()
 * @method HolzregalEntity|null get(string $key)
 * @method HolzregalEntity|null first()
 * @method HolzregalEntity|null last()
 */
class HolzregalEntityCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return HolzregalEntity::class;
    }
}

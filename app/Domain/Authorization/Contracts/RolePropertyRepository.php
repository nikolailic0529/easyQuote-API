<?php

namespace App\Domain\Authorization\Contracts;

interface RolePropertyRepository extends \IteratorAggregate
{
    /**
     * @return \Traversable<int, string>
     */
    public function getIterator(): \Traversable;
}

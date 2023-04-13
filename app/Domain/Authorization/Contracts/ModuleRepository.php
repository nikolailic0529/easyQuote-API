<?php

namespace App\Domain\Authorization\Contracts;

use App\Domain\Authorization\Repositories\Exceptions\ModuleNotFoundException;
use App\Domain\Authorization\Repositories\Models\Module;

interface ModuleRepository extends \IteratorAggregate
{
    /**
     * @throws ModuleNotFoundException
     */
    public function get(string $name): Module;

    /**
     * @return \Traversable<int, Module>
     */
    public function getIterator(): \Traversable;
}

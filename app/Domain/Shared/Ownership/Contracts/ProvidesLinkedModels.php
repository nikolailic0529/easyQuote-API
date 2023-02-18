<?php

namespace App\Domain\Shared\Ownership\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ProvidesLinkedModels
{
    /**
     * @return iterable<Model>
     */
    public function getLinkedModels(Model $model): iterable;
}

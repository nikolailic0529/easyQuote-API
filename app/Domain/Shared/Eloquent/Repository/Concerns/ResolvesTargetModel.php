<?php

namespace App\Domain\Shared\Eloquent\Repository\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesTargetModel
{
    protected function resolveTargetModel(Model $parent, Model $related): Model
    {
        if ($parent->is($related)) {
            return $parent;
        }

        return $related;
    }
}

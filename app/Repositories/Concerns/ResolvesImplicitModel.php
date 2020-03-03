<?php

namespace App\Repositories\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ResolvesImplicitModel
{
    protected function resolveModel($model)
    {
        $class = $this->model();

        if (is_string($model)) {
            return tap($this->find($model), function ($model) use ($class) {
                if (is_null($model)) {
                    throw (new ModelNotFoundException)->setModel($class, (array) $model);
                }
            });
        }

        throw_unless($model instanceof $class, new \InvalidArgumentException(
            sprintf(INV_ARG_SC_01, __METHOD__, $class) . ' ' . get_class($model) . ' given'
        ));

        return $model;
    }

    abstract public function model(): string;
}

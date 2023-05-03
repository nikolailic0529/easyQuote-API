<?php

namespace App\Domain\Shared\Eloquent\Repository\Concerns;

trait ResolvesImplicitModel
{
    protected function resolveModel($model)
    {
        $class = $this->model();

        if (is_string($model)) {
            return app($class)->findOrFail($model);
        }

        throw_unless($model instanceof $class, new \InvalidArgumentException(
            sprintf(INV_ARG_SC_01, __METHOD__, $class).' '.get_class($model).' given'
        ));

        return $model;
    }

    abstract public function model(): string;
}

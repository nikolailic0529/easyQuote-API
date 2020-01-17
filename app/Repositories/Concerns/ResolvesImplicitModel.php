<?php

namespace App\Repositories\Concerns;

trait ResolvesImplicitModel
{
    protected function resolveModel($model)
    {
        if (is_string($model)) {
            return $this->find($model);
        }

        $class = $this->model();

        throw_unless($model instanceof $class, new \InvalidArgumentException(
            sprintf(INV_ARG_SC_01, __METHOD__, $class)
        ));

        return $model;
    }

    abstract public function model(): string;
}

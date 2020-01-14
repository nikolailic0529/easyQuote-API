<?php

namespace App\Repositories\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesImplicitModel
{
    protected function resolveModel($model)
    {
        if (is_string($model)) {
            return $this->find($model);
        }

        $class = $this->model();

        throw_unless($model instanceof $class, new \InvalidArgumentException(INV_ARG_NPK_01));

        return $model;
    }

    abstract public function model(): string;
}

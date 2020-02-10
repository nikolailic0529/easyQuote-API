<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\{
    Builder,
    Model,
    Scope
};

class ContractTypeScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->where("{$model->getTable()}.document_type", Q_TYPE_CONTRACT);
    }
}

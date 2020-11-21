<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\{
    Builder,
    Model,
    Scope
};

class AnyContractTypeScope implements Scope
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
        $builder->whereIn($model->qualifyColumn('document_type'), [Q_TYPE_CONTRACT, Q_TYPE_HPE_CONTRACT]);
    }
}

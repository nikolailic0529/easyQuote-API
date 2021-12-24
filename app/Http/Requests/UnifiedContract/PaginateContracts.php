<?php

namespace App\Http\Requests\UnifiedContract;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Foundation\Http\FormRequest;

class PaginateContracts extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function transformContractsQuery(BaseBuilder $builder): BaseBuilder
    {
        return tap($builder, function (BaseBuilder $builder) {
            //
        });
    }
}

<?php

namespace App\Services;

use App\Models\Template\HpeContractTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;

class HpeContractTemplateQueries
{
    public function referencedQuery(string $id)
    {
        return HpeContractTemplate::whereKey($id)
            ->where(function (Builder $builder) {
                $builder->whereExists(
                    fn (BaseBuilder $builder) => $builder->from('hpe_contracts')->whereColumn('hpe_contracts.quote_template_id', 'hpe_contract_templates.id')->whereNull('deleted_at'),
                );
            });
    }
}
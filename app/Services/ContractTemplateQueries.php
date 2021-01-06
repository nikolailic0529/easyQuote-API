<?php

namespace App\Services;

use App\Models\Template\ContractTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;

class ContractTemplateQueries
{
    public function referencedQuery(string $id)
    {
        return ContractTemplate::whereKey($id)
            ->where(function (Builder $builder) {
                $builder->whereExists(
                    fn (BaseBuilder $builder) => $builder->from('quotes')->whereColumn('quotes.contract_template_id', 'contract_templates.id')->whereNull('deleted_at'),
                )->orWhereExists(
                    fn (BaseBuilder $builder) => $builder->from('contracts')->whereColumn('contracts.contract_template_id', 'contract_templates.id')->whereNull('deleted_at'),
                );
            });
    }
}

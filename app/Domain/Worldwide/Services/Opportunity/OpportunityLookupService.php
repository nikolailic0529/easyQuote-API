<?php

namespace App\Domain\Worldwide\Services\Opportunity;

use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Services\Opportunity\Models\OpportunityLookupParameters;
use Illuminate\Database\Eloquent\Builder;

class OpportunityLookupService
{
    public function find(OpportunityLookupParameters $parameters): ?Opportunity
    {
        return Opportunity::query()
            ->where('project_name', $parameters->project_name)
            ->when(
                value: $parameters->unit_name !== null,
                callback: static function (Builder $builder) use ($parameters): void {
                    $builder->whereBelongsTo(
                        SalesUnit::query()->where('unit_name', $parameters->unit_name)->sole()
                    );
                },
                default: static function (Builder $builder): void {
                    $builder->doesntHave('salesUnit');
                }
            )
            ->latest()
            ->first();
    }
}

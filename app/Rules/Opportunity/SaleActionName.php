<?php

namespace App\Rules\Opportunity;

use App\Models\Pipeline\Pipeline;
use App\Queries\PipelineQueries;
use App\Services\Opportunity\OpportunityDataMapper;
use Illuminate\Contracts\Validation\Rule;

class SaleActionName implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        /** @var PipelineQueries $pipelineQueries */
        $pipelineQueries = app(PipelineQueries::class);

        /** @var Pipeline $pipeline */
        $pipeline = $pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();

        $stageName = OpportunityDataMapper::resolveStageNameFromSaleAction($value);

        return $pipeline->pipelineStages()->where('stage_name', $stageName)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The given sale action is not present in the default pipeline.';
    }
}

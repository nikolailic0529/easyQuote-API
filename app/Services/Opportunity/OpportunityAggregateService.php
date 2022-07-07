<?php

namespace App\Services\Opportunity;

use App\DTO\Opportunity\PipelineStageOpportunitiesData;
use App\DTO\Opportunity\PipelineStageSummaryData;
use App\Models\Pipeline\Pipeline;
use App\Models\Pipeline\PipelineStage;
use App\Queries\OpportunityQueries;
use App\Queries\PipelineQueries;
use Illuminate\Http\Request;

class OpportunityAggregateService
{
    public function __construct(protected PipelineQueries    $pipelineQueries,
                                protected OpportunityQueries $opportunityQueries)
    {
    }

    /**
     * @return PipelineStageOpportunitiesData[]
     */
    public function getOpportunitiesGroupedByPipelineStages(Request $request = null): array
    {
        /** @var Pipeline $defaultPipeline */
        $defaultPipeline = $this->pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();

        $stageOpportunitiesCollection = [];

        foreach ($defaultPipeline->pipelineStages as $pipelineStage) {

            $summary = $this->calculateSummaryOfPipelineStage($pipelineStage);

            $pagination = $this->opportunityQueries->paginateOpportunitiesOfPipelineStageQuery($pipelineStage, $request)->apiPaginate();

            $stageOpportunitiesCollection[] = new PipelineStageOpportunitiesData([
                'stage_id' => $pipelineStage->getKey(),
                'stage_name' => $pipelineStage->stage_name,
                'stage_order' => $pipelineStage->stage_order,
                'stage_percentage' => $pipelineStage->stage_percentage,
                'summary' => $summary,
                'meta' => [
                    'current_page' => $pagination->currentPage(),
                    'per_page' => $pagination->perPage(),
                    'last_page' => $pagination->lastPage(),
                ],
                'opportunities' => $pagination->items(),
            ]);

        }

        return $stageOpportunitiesCollection;
    }

    public function calculateSummaryOfPipelineStage(PipelineStage $stage): PipelineStageSummaryData
    {
        $agg = $this->opportunityQueries->opportunitiesOfPipelineStageQuery($stage)
            ->selectRaw('count(0) as count')
            ->selectRaw('sum(base_opportunity_amount) as base_amount')
            ->toBase()
            ->first();

        return new PipelineStageSummaryData([
            'total' => (int)$agg->count,
            'valid' => (int)$agg->count,
            'invalid' => 0,
            'base_amount' => round((float)$agg->base_amount, 2),
        ]);
    }
}
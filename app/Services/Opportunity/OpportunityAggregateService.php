<?php

namespace App\Services\Opportunity;

use App\DTO\Opportunity\PipelineStageOpportunitiesData;
use App\DTO\Opportunity\PipelineStageSummaryCollection;
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
    public function getOpportunitiesGroupedByPipelineStages(Request $request): array
    {
        /** @var Pipeline $defaultPipeline */
        $defaultPipeline = $this->pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();

        $stageOpportunitiesCollection = [];

        foreach ($defaultPipeline->pipelineStages as $pipelineStage) {
            $summary = $this->calculateSummaryOfPipelineStage($pipelineStage, $request);

            $pagination = $this->opportunityQueries
                ->paginateOpportunitiesOfPipelineStageQuery($pipelineStage, $request)
                ->apiPaginate();

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

    public function calculateSummaryOfDefaultPipeline(Request $request): PipelineStageSummaryCollection
    {
        /** @var Pipeline $defaultPipeline */
        $defaultPipeline = $this->pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();

        $collection = PipelineStageSummaryCollection::make();

        foreach ($defaultPipeline->pipelineStages as $stage) {
            $collection[] = $this->calculateSummaryOfPipelineStage($stage, $request);
        }

        return $collection;
    }

    public function calculateSummaryOfPipelineStage(PipelineStage $stage, Request $request): PipelineStageSummaryData
    {
        $agg = $this->opportunityQueries->opportunitiesOfPipelineStageQuery($stage, $request)
            ->leftJoin('opportunity_validation_results as ovr', 'ovr.opportunity_id', 'opportunities.id')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(base_opportunity_amount) as base_amount')
            ->selectRaw('sum(ovr.is_passed) as valid')
            ->toBase()
            ->first();

        return new PipelineStageSummaryData([
            'stage_id' => $stage->getKey(),
            'total' => (int)$agg->count,
            'valid' => (int)$agg->valid,
            'invalid' => (int)$agg->count - (int)$agg->valid,
            'base_amount' => round((float)$agg->base_amount, 2),
        ]);
    }
}
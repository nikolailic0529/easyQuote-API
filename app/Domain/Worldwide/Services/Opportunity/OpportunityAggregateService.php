<?php

namespace App\Domain\Worldwide\Services\Opportunity;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Pipeline\Models\PipelineStage;
use App\Domain\Pipeline\Queries\PipelineQueries;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\PipelineStageOpportunitiesData;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\PipelineStageSummaryCollection;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\PipelineStageSummaryData;
use App\Domain\Worldwide\Queries\OpportunityQueries;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class OpportunityAggregateService
{
    public function __construct(protected PipelineQueries $pipelineQueries,
                                protected OpportunityQueries $opportunityQueries)
    {
    }

    /**
     * @return PipelineStageOpportunitiesData[]
     */
    public function getOpportunitiesGroupedByPipelineStages(Request $request): array
    {
        /** @var \App\Domain\Pipeline\Models\Pipeline $defaultPipeline */
        $defaultPipeline = $this->pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();

        $stageOpportunitiesCollection = [];

        $stageSummaries = $this->calculateSummaryOfMultiplePipelineStages($defaultPipeline->pipelineStages, $request);

        foreach ($defaultPipeline->pipelineStages as $stage) {
            $pagination = $this->opportunityQueries
                ->paginateOpportunitiesOfPipelineStageQuery($stage, $request)
                ->apiPaginate();

            $stageOpportunitiesCollection[] = new PipelineStageOpportunitiesData([
                'stage_id' => $stage->getKey(),
                'stage_name' => $stage->stage_name,
                'stage_order' => $stage->stage_order,
                'stage_percentage' => $stage->stage_percentage,
                'summary' => $stageSummaries[$stage->getKey()],
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

    /**
     * @return array<string, PipelineStageSummaryData>
     */
    public function calculateSummaryOfMultiplePipelineStages(Collection $stages, Request $request): array
    {
        $agg = $this->opportunityQueries->aggregateOpportunitiesOfMultiplePipelineStagesQuery($stages, $request)
            ->get()
            ->keyBy('stage_id');

        return $stages->mapWithKeys(static function (PipelineStage $stage) use ($agg) {
            $agg = (array) ($agg[$stage->getKey()] ?? []);
            $agg = [...['count' => 0, 'valid' => 0, 'base_amount' => 0.0], ...$agg];

            return [
                $stage->getKey() => new PipelineStageSummaryData([
                    'stage_id' => $stage->getKey(),
                    'total' => (int) $agg['count'],
                    'valid' => (int) $agg['valid'],
                    'invalid' => (int) $agg['count'] - (int) $agg['valid'],
                    'base_amount' => round((float) $agg['base_amount'], 2),
                ]),
            ];
        })
            ->all();
    }

    public function calculateSummaryOfPipelineStage(PipelineStage $stage, Request $request): PipelineStageSummaryData
    {
        $agg = $this->opportunityQueries->opportunitiesOfPipelineStageQuery($stage, $request)
            ->select([])
            ->reorder()
            ->leftJoin('opportunity_validation_results as ovr', 'ovr.opportunity_id', 'opportunities.id')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(base_opportunity_amount) as base_amount')
            ->selectRaw('sum(ovr.is_passed) as valid')
            ->toBase()
            ->first();

        return new PipelineStageSummaryData([
            'stage_id' => $stage->getKey(),
            'total' => (int) $agg->count,
            'valid' => (int) $agg->valid,
            'invalid' => (int) $agg->count - (int) $agg->valid,
            'base_amount' => round((float) $agg->base_amount, 2),
        ]);
    }
}

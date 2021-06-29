<?php

namespace App\Services\Opportunity;

use App\DTO\Opportunity\PipelineStageOpportunitiesData;
use App\Models\Opportunity;
use App\Models\Pipeline\Pipeline;
use App\Queries\OpportunityQueries;
use App\Queries\PipelineQueries;
use App\Services\Pipeline\PipelineEntityService;
use Illuminate\Database\Query\JoinClause;

class OpportunityAggregateService
{
    public function __construct(protected PipelineQueries $pipelineQueries, protected OpportunityQueries $opportunityQueries)
    {
    }

    /**
     * @return PipelineStageOpportunitiesData[]
     */
    public function getOpportunitiesGroupedByPipelineStages(): array
    {
        /** @var Pipeline $defaultPipeline */
        $defaultPipeline = $this->pipelineQueries->explicitlyDefaultPipelinesQuery()->sole();

        $stageOpportunitiesCollection = [];

        foreach ($defaultPipeline->pipelineStages as $pipelineStage) {

            $opportunities = $this->opportunityQueries->opportunitiesOfPipelineStageQuery($pipelineStage)->get();

            $stageOpportunitiesCollection[] = new PipelineStageOpportunitiesData([
                'stage_id' => $pipelineStage->getKey(),
                'stage_name' => $pipelineStage->stage_name,
                'stage_order' => $pipelineStage->stage_order,
                'opportunities' => $opportunities->all(),
            ]);

        }

        return $stageOpportunitiesCollection;
    }
}
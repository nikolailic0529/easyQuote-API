<?php

namespace App\Http\Resources\V1\Opportunity;

use App\DTO\Opportunity\PipelineStageOpportunitiesData;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GroupedOpportunityCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return $this->resource
            ->map(static function (PipelineStageOpportunitiesData $stage): array {
                return [...$stage->except('opportunities')->toArray(), ...[
                    'opportunities' => OpportunityOfPipelineStageResource::collection($stage->opportunities),
                ]];
            });
    }
}

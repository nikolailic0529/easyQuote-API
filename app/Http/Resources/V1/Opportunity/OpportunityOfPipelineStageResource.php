<?php

namespace App\Http\Resources\V1\Opportunity;

use App\Models\Opportunity;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Opportunity
 */
class OpportunityOfPipelineStageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = $request->user();

        $this->primaryAccount?->append('logo');
        $this->endUser?->append('logo');

        $array = parent::toArray($request);

        $array['permissions'] = [
            'view' => $user->can('view', $this->resource),
            'update' => $user->can('update', $this->resource),
            'delete' => $user->can('delete', $this->resource),
        ];

        $array['validation_result'] = OpportunityValidationResource::make($this->validationResult);

        unset($array['worldwide_quotes']);
        $array['quote'] = (function (): ?QuoteOfOpportunityResource {
            if ($this->worldwideQuotes->isEmpty()) {
                return null;
            }

            return QuoteOfOpportunityResource::make($this->worldwideQuotes->first());
        })();

        return array_merge($array, $this->additional);
    }
}

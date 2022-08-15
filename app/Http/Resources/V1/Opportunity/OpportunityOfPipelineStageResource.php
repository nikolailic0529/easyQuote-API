<?php

namespace App\Http\Resources\V1\Opportunity;

use App\Models\Opportunity;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityOfPipelineStageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var Opportunity|OpportunityOfPipelineStageResource $this */

        $user = $request->user();

        $this->primaryAccount?->append('logo');
        $this->endUser?->append('logo');

        return array_merge(parent::toArray($request), [
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'validation_result' => OpportunityValidationResource::make($this->validationResult),
        ], $this->additional);
    }
}

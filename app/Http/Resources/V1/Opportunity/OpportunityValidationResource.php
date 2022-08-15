<?php

namespace App\Http\Resources\V1\Opportunity;

use App\Models\OpportunityValidationResult;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityValidationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var OpportunityValidationResult|OpportunityValidationResource $this */

        return [
          'id' => $this->getKey(),
          'opportunity_id' => $this->opportunity()->getParentKey(),
          'messages' => $this->messages->all(),
          'is_passed' => (bool)$this->is_passed,
        ];
    }
}
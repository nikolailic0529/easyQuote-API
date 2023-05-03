<?php

namespace App\Domain\Worldwide\Resources\V1\Opportunity;

use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityValidationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /* @var \App\Domain\Worldwide\Models\OpportunityValidationResult|OpportunityValidationResource $this */

        return [
          'id' => $this->getKey(),
          'opportunity_id' => $this->opportunity()->getParentKey(),
          'messages' => $this->messages->all(),
          'is_passed' => (bool) $this->is_passed,
        ];
    }
}

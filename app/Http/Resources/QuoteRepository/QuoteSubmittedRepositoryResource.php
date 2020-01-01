<?php

namespace App\Http\Resources\QuoteRepository;

use Illuminate\Http\Resources\Json\JsonResource;

class QuoteSubmittedRepositoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user_id,
                'first_name' => $this->cached_relations->user->first_name,
                'last_name' => $this->cached_relations->user->last_name
            ],
            'company' => [
                'id' => $this->company_id,
                'name' => $this->cached_relations->company->name
            ],
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->cached_relations->customer->name,
                'rfq' => $this->cached_relations->customer->rfq,
                'valid_until' => $this->cached_relations->customer->valid_until,
                'support_start' => $this->cached_relations->customer->support_start,
                'support_end' => $this->cached_relations->customer->support_end
            ],
            'last_drafted_step' => $this->last_drafted_step,
            'completeness' => $this->completeness,
            'created_at' => $this->created_at,
            'activated_at' => $this->activated_at,
        ];
    }
}

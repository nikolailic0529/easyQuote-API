<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class QuoteRepositoryResource extends JsonResource
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
            'user' => $this->user,
            'company' => $this->company,
            'created_at' => $this->created_at,
            'activated_at' => $this->activated_at,
            'completeness' => $this->completeness,
            'customer' => [
                'name' => $this->customer->name,
                'rfq' => $this->customer->rfq,
                'valid_until' => $this->customer->valid_until,
                'support_start' => $this->customer->support_start,
                'support_end' => $this->customer->support_end
            ],
            'last_drafted_step' => $this->last_drafted_step
        ];
    }
}

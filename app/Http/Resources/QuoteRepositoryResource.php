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
            'customer' => $this->customer,
            'last_drafted_step' => $this->last_drafted_step
        ];
        return parent::toArray($request);
    }
}

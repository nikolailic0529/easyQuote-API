<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Quote\Quote;

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
            'user' => [
                'id' => $this->user_id,
                'first_name' => $this->user_first_name,
                'last_name' => $this->user_last_name
            ],
            'company' => [
                'id' => $this->company_id,
                'name' => $this->company_name
            ],
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer_name,
                'rfq' => $this->customer_rfq,
                'valid_until' => carbon_format($this->customer_valid_until, config('date.format_with_time')),
                'support_start' => carbon_format($this->customer_support_start, config('date.format_with_time')),
                'support_end' => carbon_format($this->customer_support_end, config('date.format_with_time'))
            ],
            'last_drafted_step' => Quote::transformDraftedStep($this->completeness),
            'completeness' => $this->completeness,
            'created_at' => carbon_format($this->created_at, config('date.format_with_time')),
            'activated_at' => carbon_format($this->activated_at, config('date.format_with_time')),
        ];
    }
}

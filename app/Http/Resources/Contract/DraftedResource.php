<?php

namespace App\Http\Resources\Contract;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\QuoteCustomerResource;

class DraftedResource extends JsonResource
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
            'id'                => $this->id,
            'quote_id'          => $this->quote_id,
            'user' => [
                'id'            => $this->user_id,
                'first_name'    => $this->cached_relations->user->first_name,
                'last_name'     => $this->cached_relations->user->last_name
            ],
            'company' => [
                'id'            => $this->company_id,
                'name'          => $this->cached_relations->company->name
            ],
            'contract_customer' => [
                'rfq'           => $this->contract_number,
            ],
            'quote_customer'    => QuoteCustomerResource::make($this),
            'created_at'        => optional($this->created_at)->format(config('date.format_time')),
            'updated_at'        => optional($this->usingVersionFromSelection->updated_at)->format(config('date.format_time')),
            'activated_at'      => $this->activated_at,
        ];
    }
}

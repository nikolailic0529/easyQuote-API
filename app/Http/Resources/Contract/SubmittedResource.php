<?php

namespace App\Http\Resources\Contract;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\QuoteCustomerResource;

class SubmittedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\User */
        $user = $request->user();

        $modelKey = $this->document_type === Q_TYPE_HPE_CONTRACT ? $this->hpe_contract_id : $this->id;

        return [
            'id'                => $modelKey,
            'quote_id'          => $this->quote_id,
            'type'              => $this->document_type,
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
                'rfq'           => $this->contract_number
            ],
            'permissions'               => [
                'view'      => $user->can('view', $this->resource),
                'update'    => $user->can('update', $this->resource),
                'delete'    => $user->can('delete', $this->resource),
            ],
            'quote_customer'    => QuoteCustomerResource::make($this),
            'created_at'        => optional($this->created_at)->format(config('date.format_time')),
            // 'submitted_at'      => $this->submitted_at,
            'activated_at'      => $this->activated_at,
        ];
    }
}

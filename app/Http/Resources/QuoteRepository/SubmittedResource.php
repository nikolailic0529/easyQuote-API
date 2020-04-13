<?php

namespace App\Http\Resources\QuoteRepository;

use Illuminate\Http\Resources\Json\JsonResource;

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
        $user = $request->user();

        return [
            'id'                        => $this->id,
            'user' => [
                'id'                    => $this->user_id,
                'first_name'            => $this->cached_relations->user->first_name,
                'last_name'             => $this->cached_relations->user->last_name
            ],
            'company' => [
                'id'                    => $this->usingVersion->company_id,
                'name'                  => $this->usingVersion->cached_relations->company->name
            ],
            'customer' => [
                'id'                    => $this->customer_id,
                'name'                  => $this->cached_relations->customer->name,
                'rfq'                   => $this->cached_relations->customer->rfq,
                'valid_until'           => $this->cached_relations->customer->valid_until,
                'support_start'         => $this->cached_relations->customer->support_start,
                'support_end'           => $this->cached_relations->customer->support_end
            ],
            'permissions'               => [
                'view'      => $user->can('view', $this->resource),
                'update'    => $user->can('update', $this->resource),
                'delete'    => $user->can('delete', $this->resource),
            ],
            'last_drafted_step'         => $this->last_drafted_step,
            'completeness'              => $this->completeness,
            'contract_id'               => $this->contract->id,
            'has_contract_template'     => (bool) $this->hasContractTemplate,
            'has_contract'              => (bool) $this->hasContract,
            'contract_submitted'        => (bool) $this->contractSubmitted,
            'contract_number'           => $this->contract->contract_number,
            'has_price_list'            => $this->whenLoaded('usingVersion', fn () => $this->usingVersion->quoteFiles->contains('file_type', QFT_PL)),
            'price_list_filename'       => $this->whenLoaded('usingVersion', fn () => $this->usingVersion->resolveQuoteFile(QFT_PL)->original_file_name),
            'has_payment_schedule'      => $this->whenLoaded('usingVersion', fn () => $this->usingVersion->quoteFiles->contains('file_type', QFT_PS)),
            'payment_schedule_filename' => $this->whenLoaded('usingVersion', fn () => $this->usingVersion->resolveQuoteFile(QFT_PS)->original_file_name),
            'created_at'                => optional($this->created_at)->format(config('date.format_time')),
            'activated_at'              => $this->activated_at,
        ];
    }
}

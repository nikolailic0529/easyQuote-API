<?php

namespace App\Http\Resources\Contract;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

        return [
            'id'                => $this->id,
            'quote_id'          => $this->quote_id,
            'type'              => $this->document_type,
            'class'             => Str::snake(class_basename($this->resource)),
            'user' => [
                'id'            => $this->user_id,
                'first_name'    => $this->user_first_name,
                'last_name'     => $this->user_last_name
            ],
            'company' => [
                'id'            => $this->company_id,
                'name'          => $this->company_name
            ],
            'contract_customer' => [
                'rfq'           => $this->contract_number
            ],
            'permissions'               => [
                'view'      => $user->can('view', $this->resource),
                'update'    => $user->can('update', $this->resource),
                'delete'    => $user->can('delete', $this->resource),
            ],
            'quote_customer'    => [
                'id'            => $this->customer_id,
                'name'          => transform($this->customer_name, fn ($value) => $value === 'null' ? null : $value),
                'rfq'           => $this->customer_rfq_number,
                'valid_until'   => transform($this->customer_valid_until_date, fn ($date) => Carbon::parse($date)->format(config('date.format_eu'))),
                'support_start' => transform($this->customer_support_start_date, fn ($date) => Carbon::parse($date)->format(config('date.format_eu'))),
                'support_end'   => transform($this->customer_support_end_date, fn ($date) => Carbon::parse($date)->format(config('date.format_eu'))),
            ],
            'created_at'        => optional($this->created_at)->format(config('date.format_time')),
            // 'submitted_at'      => $this->submitted_at,
            'activated_at'      => $this->activated_at,
        ];
    }
}

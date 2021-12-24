<?php

namespace App\Http\Resources\Contract;

use App\Models\Quote\Contract;
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

        /** @var \App\Models\Quote\Contract|\App\Models\HpeContract|\App\Http\Resources\Contract\DraftedResource $this */

        return [
            'id'                => $this->getKey(),
            'quote_id'          => $this->quote_id,
            'type'              => $this->document_type,
            'user' => [
                'id'            => $this->user_id,
                'first_name'    => $this->user_first_name,
                'last_name'     => $this->user_last_name,
            ],
            'company' => [
                'id'            => $this->company_id,
                'name'          => $this->company_name,
            ],
            'contract_customer' => [
                'rfq' => value(function () {

                    /** @var \App\Models\Quote\Contract|\App\Models\HpeContract|\App\Http\Resources\Contract\DraftedResource $this */

                    if ($this->resource instanceof Contract) {
                        return Str::replaceFirst('CQ', 'CT', $this->customer_rfq_number);
                    }

                    return $this->customer_rfq_number;

                }),
            ],
            'permissions'               => [
                'view'      => $user->can('view', $this->resource),
                'update'    => $user->can('update', $this->resource),
                'delete'    => $user->can('delete', $this->resource),
            ],
            'quote_customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer_name,
                'rfq' => $this->customer_rfq_number,
                'valid_until' => transform($this->valid_until_date, fn (string $date) => Carbon::parse($date)->format(config('date.format_time'))),
                'support_start' => transform($this->support_start_date, fn (string $date) => Carbon::parse($date)->format(config('date.format_time'))),
                'support_end' => transform($this->support_end_date, fn (string $date) => Carbon::parse($date)->format(config('date.format_time'))),
            ],
            'created_at'        => optional($this->created_at)->format(config('date.format_time')),
            // 'submitted_at'      => $this->submitted_at,
            'activated_at'      => $this->activated_at,
        ];
    }
}

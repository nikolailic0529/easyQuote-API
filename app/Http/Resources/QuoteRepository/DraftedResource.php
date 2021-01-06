<?php

namespace App\Http\Resources\QuoteRepository;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\{Str, Carbon};

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
        $user = $request->user();

        $userFullname = Str::of($this->user_fullname)->explode(' ');

        return [
            'id'                        => $this->id,
            'user' => [
                'id'                    => $this->user_id,
                'first_name'            => $userFullname->first(),
                'last_name'             => $userFullname->last(),
            ],
            'company' => [
                'id'                    => $this->activeVersionOrCurrent->company_id,
                'name'                  => $this->activeVersionOrCurrent->company_name ?? CP_DEF_NAME
            ],
            'customer' => [
                'id'                    => $this->customer_id,
                'name'                  => $this->customer_name,
                'rfq'                   => $this->customer_rfq_number,
                'source'                => __($this->customer_source),

                'valid_until'           => optional($this->customer_valid_until_date, fn ($date) => Carbon::parse($date))->format(config('date.format')),
                'support_start'         => optional($this->customer_support_start_date, fn ($date) => Carbon::parse($date))->format(config('date.format')),
                'support_end'           => optional($this->customer_support_end_date, fn ($date) => Carbon::parse($date))->format(config('date.format')),
            ],
            'permissions'               => [
                'view'      => $user->can('view', $this->resource),
                'update'    => $user->can('update', $this->resource),
                'delete'    => $user->can('delete', $this->resource),
            ],
            'has_versions'              => $this->has_versions,
            'versions'                  => $this->versionsSelection,
            'last_drafted_step'         => $this->activeVersionOrCurrent->last_drafted_step,
            'completeness'              => $this->activeVersionOrCurrent->completeness,
            'is_author'                 => $this->user_id === auth()->id(),
            
            'has_price_list'            => !is_null($this->activeVersionOrCurrent->distributor_file_id),
            'price_list_filename'       => $this->activeVersionOrCurrent->price_list_original_file_name,
            'has_payment_schedule'      => !is_null($this->activeVersionOrCurrent->schedule_file_id),
            'payment_schedule_filename' => $this->activeVersionOrCurrent->payment_schedule_original_file_name,

            'created_at'                => optional($this->created_at)->format(config('date.format_time')),
            'updated_at'                => optional($this->activeVersionOrCurrent->updated_at)->format(config('date.format_time')),
            'activated_at'              => $this->activated_at,
        ];
    }
}

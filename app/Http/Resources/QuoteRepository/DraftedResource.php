<?php

namespace App\Http\Resources\QuoteRepository;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

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

        return [
            'id'                        => $this->id,
            'user' => [
                'id'                    => $this->user_id,
                'first_name'            => $this->cached_relations->user->first_name,
                'last_name'             => $this->cached_relations->user->last_name
            ],
            'company' => [
                'id'                    => $this->usingVersion->company_id,
                'name'                  => $this->usingVersion->cached_relations->company->name ?? CP_DEF_NAME
            ],
            'customer' => [
                'id'                    => $this->customer_id,
                'name'                  => $this->cached_relations->customer->name,
                'rfq'                   => $this->cached_relations->customer->rfq,
                'source'                => __($this->cached_relations->customer->source),

                'valid_until'           => optional($this->cached_relations->customer->valid_until_date, fn ($date) => Carbon::parse($date))->format(config('date.format')),
                'support_start'         => optional($this->cached_relations->customer->support_start_date, fn ($date) => Carbon::parse($date))->format(config('date.format')),
                'support_end'           => optional($this->cached_relations->customer->support_end_date, fn ($date) => Carbon::parse($date))->format(config('date.format')),
            ],
            'permissions'               => [
                'view'      => $user->can('view', $this->resource),
                'update'    => $user->can('update', $this->resource),
                'delete'    => $user->can('delete', $this->resource),
            ],
            'has_versions'              => $this->has_versions,
            'versions'                  => $this->versionsSelection,
            'last_drafted_step'         => $this->usingVersionFromSelection->last_drafted_step,
            'completeness'              => $this->usingVersionFromSelection->completeness,
            'is_author'                 => $this->user_id === auth()->id(),
            'has_price_list'            => $this->whenLoaded('usingVersion', fn () => $this->usingVersion->quoteFiles->contains('file_type', QFT_PL)),
            'price_list_filename'       => $this->whenLoaded('usingVersion', fn () => $this->usingVersion->resolveQuoteFile(QFT_PL)->original_file_name),
            'has_payment_schedule'      => $this->whenLoaded('usingVersion', fn () => $this->usingVersion->quoteFiles->contains('file_type', QFT_PS)),
            'payment_schedule_filename' => $this->whenLoaded('usingVersion', fn () => $this->usingVersion->resolveQuoteFile(QFT_PS)->original_file_name),
            'created_at'                => optional($this->created_at)->format(config('date.format_time')),
            'updated_at'                => optional($this->usingVersionFromSelection->updated_at)->format(config('date.format_time')),
            'activated_at'              => $this->activated_at,
        ];
    }
}

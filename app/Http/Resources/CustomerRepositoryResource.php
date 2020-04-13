<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerRepositoryResource extends JsonResource
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
            'id'            => $this->id,
            'name'          => $this->name,
            'rfq'           => $this->rfq,
            'valid_until'   => $this->valid_until_date,
            'support_start' => $this->support_start_date,
            'support_end'   => $this->support_end_date,
            'created_at'    => optional($this->created_at)->format(config('date.format_time'))
        ];
    }
}

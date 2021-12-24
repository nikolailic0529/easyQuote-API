<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
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
            'log_name'      => $this->log_name,
            'description'   => $this->description,
            'subject_id'    => $this->subject_id,
            'subject_name'  => $this->subject_name,
            'subject_type'  => $this->subject_type_base,
            'causer_name'   => $this->causer_name ?? $this->causer_service_name,
            'changes'       => $this->attribute_changes,
            'created_at'    => optional($this->created_at)->format(config('date.format_time')),
        ];
    }
}

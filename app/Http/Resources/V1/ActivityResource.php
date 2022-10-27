<?php

namespace App\Http\Resources\V1;

use App\Models\System\Activity;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Activity
 */
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
            'id'            => $this->getKey(),
            'log_name'      => $this->log_name,
            'description'   => $this->description,
            'subject_id'    => $this->subject_id,
            'subject_name'  => $this->subject_name,
            'subject_type'  => $this->subject_type_base,
            'causer_name'   => $this->causer_name ?? $this->causer_service_name,
            'changes'       => $this->attribute_changes,
            'created_at'    => format('date_time', $this->{$this->getCreatedAtColumn()}),
        ];
    }
}

<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceResource extends JsonResource
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
            'build_number'          => $this->build_number,
            'git_tag'               => $this->git_tag,
            'maintenance_message'   => $this->maintenance_message,
            'enabled'               => now()->lt($this->end_time),
            'start_time'            => (string) $this->start_time,
            'end_time'              => (string) $this->end_time,
            'created_at'            => (string) $this->created_at
        ];
    }
}

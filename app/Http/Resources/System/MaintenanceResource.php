<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Facades\Maintenance;
use Queue;

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
            'maintenance_message'   => setting('maintenance_message'),
            'pending_queues'        => Queue::size(),
            'scheduled'             => Maintenance::scheduled(),
            'enabled'               => Maintenance::running(),
            'start_time'            => (string) optional($this->start_time)->toISOString(),
            'end_time'              => (string) optional($this->end_time)->toISOString(),
            'created_at'            => (string) optional($this->created_at)->toISOString()
        ];
    }
}

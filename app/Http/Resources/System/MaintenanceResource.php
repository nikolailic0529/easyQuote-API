<?php

namespace App\Http\Resources\System;

use Illuminate\Http\Resources\Json\JsonResource;
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
        $now = now();

        return [
            'build_number'          => $this->build_number,
            'git_tag'               => $this->git_tag,
            'maintenance_message'   => setting('maintenance_message'),
            'pending_queues'        => Queue::size(),
            'scheduled'             => $now->lt($this->start_time),
            'enabled'               => $now->gte($this->start_time) && $now->lte($this->end_time),
            'start_time'            => (string) optional($this->start_time)->toISOString(),
            'end_time'              => (string) optional($this->end_time)->toISOString(),
            'created_at'            => (string) $this->created_at->toISOString()
        ];
    }
}

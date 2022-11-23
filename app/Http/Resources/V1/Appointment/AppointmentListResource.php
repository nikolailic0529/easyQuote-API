<?php

namespace App\Http\Resources\V1\Appointment;

use App\Http\Resources\V1\User\UserRelationResource;
use App\Models\Appointment\Appointment;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $tz = $request->user()->timezone->utc ?? config('app.timezone');

        return [
            'id' => $this->getKey(),
            'activity_type' => $this->activity_type,
            'user' => UserRelationResource::make($this->owner),
            'subject' => $this->subject,
            'start_date' => $this->start_date?->tz($tz)?->format(config('date.format_time')),
            'end_date' => $this->end_date?->tz($tz)?->format(config('date.format_time')),
            'location' => $this->location,
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}

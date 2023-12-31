<?php

namespace App\Domain\Task\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TaskRecurrenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var \App\Domain\Task\Models\TaskRecurrence|self $this */
        $tz = $request->user()->timezone->utc ?? config('app.timezone');

        return [
            'id' => $this->getKey(),
            'user_id' => $this->user()->getParentKey(),
            'task_id' => $this->task()->getParentKey(),

            'type' => $this->type->value,
            'day' => $this->day->value,
            'week' => $this->week->value,
            'day_of_week' => $this->day_of_week,
            'month' => $this->month->value,
            'occur_every' => $this->occur_every,
            'occurrences_count' => $this->occurrences_count,

            'start_date' => Carbon::instance($this->start_date)->setTimezone($tz)->format(config('date.format_time')),
            'end_date' => isset($this->end_date)
                ? Carbon::instance($this->end_date)->setTimezone($tz)->format(config('date.format_time'))
                : null,

            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}

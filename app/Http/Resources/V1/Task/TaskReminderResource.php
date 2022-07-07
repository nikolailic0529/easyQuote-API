<?php

namespace App\Http\Resources\V1\Task;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskReminderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        /** @var \App\Models\Task\TaskReminder|self $this */

        $tz = $request->user()->timezone->utc ?? config('app.timezone');

        return [
            'id' => $this->getKey(),
            'task_id' => $this->task()->getParentKey(),
            'user_id' => $this->user()->getParentKey(),
            'set_date' => Carbon::instance($this->set_date)->setTimezone($tz)->format(config('date.format_time')),
            'status' => $this->status,
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}

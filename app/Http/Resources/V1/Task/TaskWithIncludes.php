<?php

namespace App\Http\Resources\V1\Task;

use App\Http\Resources\V1\Attachment\CreatedAttachment;
use App\Http\Resources\V1\User\UserRelationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskWithIncludes extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Task\Task|self $this */

        $tz = $request->user()->timezone->utc ?? config('app.timezone');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => UserRelationResource::make($this->user),
            'activity_type' => $this->activity_type,
            'name' => $this->name,
            'content' => $this->content,
            'expiry_date' => $this->expiry_date?->tz($tz)?->format(config('date.format_time')),
            'priority' => $this->priority,

            'recurrence' => TaskRecurrenceResource::make($this->recurrence),
            'reminder' => TaskReminderResource::make($this->reminder),

            'linked_relations' => $this->linkedModelRelations,

            'users' => UserRelationResource::collection($this->users),
            'attachments' => CreatedAttachment::collection($this->attachments),

            'created_at' => $this->created_at?->tz($tz)?->format(config('date.format_time')),
            'updated_at' => $this->updated_at?->tz($tz)?->format(config('date.format_time')),
        ];
    }
}

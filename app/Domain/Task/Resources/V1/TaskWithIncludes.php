<?php

namespace App\Domain\Task\Resources\V1;

use App\Domain\Attachment\Resources\V1\CreatedAttachment;
use App\Domain\Task\Models\TaskReminder;
use App\Domain\User\Resources\V1\UserRelationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskWithIncludes extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Domain\Task\Models\Task|self $this */
        $tz = $request->user()->timezone->utc ?? config('app.timezone');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => UserRelationResource::make($this->user),
            'sales_unit_id' => $this->salesUnit()->getParentKey(),
            'activity_type' => $this->activity_type,
            'name' => $this->name,
            'content' => $this->content,
            'expiry_date' => $this->expiry_date?->tz($tz)?->format(config('date.format_time')),
            'priority' => $this->priority,

            'sales_unit' => $this->salesUnit,
            'recurrence' => TaskRecurrenceResource::make($this->recurrence),
            'reminder' => TaskReminderResource::make($this->activeReminders->first(
                static function (TaskReminder $reminder) use ($request): bool {
                    return $reminder->user()->is($request->user());
                })
            ),

            'linked_relations' => $this->linkedModelRelations,

            'users' => UserRelationResource::collection($this->users),
            'attachments' => CreatedAttachment::collection($this->attachments),

            'created_at' => $this->created_at?->tz($tz)?->format(config('date.format_time')),
            'updated_at' => $this->updated_at?->tz($tz)?->format(config('date.format_time')),
        ];
    }
}

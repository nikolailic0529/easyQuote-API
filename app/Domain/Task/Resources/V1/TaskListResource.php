<?php

namespace App\Domain\Task\Resources\V1;

use App\Domain\Attachment\Resources\V1\CreatedAttachment;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\User;
use App\Domain\User\Resources\V1\UserRelationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskListResource extends JsonResource
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
        /** @var Task|self $this */

        /** @var User $user */
        $user = $request->user();

        $tz = $user->timezone->utc ?? config('app.timezone');

        return [
            'id' => $this->id,

            'user_id' => $this->user()->getParentKey(),
            'user' => UserRelationResource::make($this->whenLoaded('user')),

            'sales_unit_id' => $this->salesUnit()->getParentKey(),

            'taskable_id' => $this->model_id,
            'taskable_type' => $this->model_type,

            'activity_type' => $this->activity_type,
            'name' => $this->name,
            'content' => $this->content,
            'expiry_date' => $this->expiry_date?->tz($tz)?->format(config('date.format_time')),
            'priority' => $this->priority,

            'users' => UserRelationResource::collection($this->whenLoaded('users')),
            'attachments' => CreatedAttachment::collection($this->whenLoaded('attachments')),

            'created_at' => $this->created_at?->tz($tz)?->format(config('date.format_time')),
            'updated_at' => $this->updated_at?->tz($tz)?->format(config('date.format_time')),
        ];
    }
}

<?php

namespace App\Http\Resources\Task;

use App\Http\Resources\AttachmentResource;
use App\Http\Resources\User\UserRelationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskListResource extends JsonResource
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
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'user'          => UserRelationResource::make($this->whenLoaded('user')),
            'taskable_id'   => $this->taskable_id,
            'name'          => $this->name,
            'content'       => $this->content,
            'expiry_date'   => optional($this->expiry_date)->format(config('date.format_time')),
            'priority'      => (int) $this->priority,
            'users'         => UserRelationResource::collection($this->whenLoaded('users')),
            'attachments'   => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at'    => optional($this->created_at)->format(config('date.format_time')),
            'updated_at'    => optional($this->updated_at)->format(config('date.format_time')),
        ];
    }
}

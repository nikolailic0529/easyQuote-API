<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'id' => $this->id,
            'user_id' => $this->user_id,
            'message' => $this->message,
            'url' => $this->url,
            'priority' => $this->priority,
            'read' => $this->read,
            'created_at' => $this->created_at,
            'read_at' => $this->read_at
        ];
    }
}

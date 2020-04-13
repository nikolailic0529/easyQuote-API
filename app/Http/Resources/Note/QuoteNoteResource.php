<?php

namespace App\Http\Resources\Note;

use App\Http\Resources\User\UserRelationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteNoteResource extends JsonResource
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
            'quote_id' => $this->quote_id,
            'user_id' => $this->user_id,
            'user' => UserRelationResource::make($this->whenLoaded('user')),
            'text' => $this->text,
            'created_at' => optional($this->created_at)->format(config('date.format_time')),
            'updated_at' => optional($this->updated_at)->format(config('date.format_time')),
        ];
    }
}

<?php

namespace App\Http\Resources\V1\Note;

use App\Http\Resources\V1\User\UserRelationResource;
use App\Models\Note\Note;
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
        /** @var Note|self $this */

        return [
            'id' => $this->getKey(),
            'user_id' => $this->owner()->getParentKey(),
            'user' => UserRelationResource::make($this->whenLoaded('owner')),
            'text' => $this->note,
            'created_at' => $this->created_at?->format(config('date.format_time')),
            'updated_at' => $this->updated_at?->format(config('date.format_time')),
        ];
    }
}

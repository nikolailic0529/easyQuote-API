<?php

namespace App\Http\Resources\Note;

use App\Http\Resources\User\UserRelationResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorldwideQuoteNoteList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Quote\WorldwideQuoteNote|WorldwideQuoteNoteList $this */

        return [
            'id' => $this->id,
            'worldwide_quote_id' => $this->worldwide_quote_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user'),
            'text' => $this->text,
            'created_at' => optional($this->created_at)->format(config('date.format_time')),
            'updated_at' => optional($this->updated_at)->format(config('date.format_time')),
        ];
    }
}

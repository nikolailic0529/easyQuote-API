<?php

namespace App\Domain\Note\Resources\V1;

use App\Domain\Note\Models\Note;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Http\Resources\Json\JsonResource;

class WorldwideQuoteNoteList extends JsonResource
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
        /** @var \App\Domain\Note\Models\Note|WorldwideQuoteNoteList $this */

        /** @var Authorizable $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'user_id' => $this->owner()->getParentKey(),
            'user' => $this->whenLoaded('owner'),
            'text' => $this->note,
            'is_system' => (bool) $this->getFlag(Note::SYSTEM),
            'permissions' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->created_at?->format(config('date.format_time')),
            'updated_at' => $this->updated_at?->format(config('date.format_time')),
        ];
    }
}

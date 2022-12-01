<?php

namespace App\Http\Resources\V1\OpportunityNote;

use App\Models\Note\Note;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityNoteListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var Authorizable $user */
        $user = $request->user();

        /** @var Note|self $this */
        return [
            'id' => $this->getKey(),
            'user_id' => $this->owner()->getParentKey(),
            'owner' => $this->whenLoaded('owner'),
            'text' => $this->note,
            'is_system' => (bool)$this->getFlag(Note::SYSTEM),
            'permissions' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

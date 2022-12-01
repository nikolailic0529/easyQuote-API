<?php

namespace App\Http\Resources\V1\Note;

use App\Models\Note\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class UnifiedNoteOfCompany extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Note\Note $this */

        /** @var User|null $user */
        $user = $request->user();

        $parentEntityType = match ($parentEntityType = class_basename(Relation::getMorphedModel($this->model_type))) {
            'WorldwideQuote' => 'Quote',
            default => $parentEntityType,
        };

        return [
            'id' => $this->getKey(),
            'note_entity_type' => $this->getMorphClass(),
            'note_entity_class' => Str::snake("$parentEntityType note"),
            'parent_entity_type' => $parentEntityType,
            'quote_id' => $this->quote_id,
            'customer_id' => $this->customer_id,
            'quote_number' => $this->quote_number,
            'text' => $this->note,
            'owner_user_id' => $this->user_id,
            'owner_fullname' => $this->user_fullname,
            'is_system' => (bool) $this->getFlag(Note::SYSTEM),
            'permissions' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->created_at,

        ];
    }
}

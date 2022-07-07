<?php

namespace App\Http\Resources\V1\OpportunityNote;

use App\Models\Note\Note;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityNoteWithIncludesResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var Note|self $this */

        return [
            'id' => $this->getKey(),
            'user_id' => $this->owner()->getParentKey(),
            'user' => $this->whenLoaded('owner'),
            'text' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

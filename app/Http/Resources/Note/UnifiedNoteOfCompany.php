<?php

namespace App\Http\Resources\Note;

use App\Models\Quote\QuoteNote;
use App\Models\Quote\WorldwideQuoteNote;
use App\Models\User;
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
        /** @var QuoteNote|WorldwideQuoteNote|UnifiedNoteOfCompany $this */

        /** @var User|null $user */
        $user = $request->user();

        return [

            'id' => $this->getKey(),
            'note_entity_type' => $this->getMorphClass(),
            'note_entity_class' => Str::snake(class_basename($this->resource)),
            'quote_id' => $this->quote_id,
            'customer_id' => $this->customer_id,
            'quote_number' => $this->quote_number,
            'text' => $this->text,
            'owner_user_id' => $this->user_id,
            'owner_fullname' => $this->user_fullname,
            'permissions' => [
              'update' => $user->can('update', $this->resource),
              'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->created_at,

        ];
    }
}

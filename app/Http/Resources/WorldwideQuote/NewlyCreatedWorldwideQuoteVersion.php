<?php

namespace App\Http\Resources\WorldwideQuote;

use Illuminate\Http\Resources\Json\JsonResource;

class NewlyCreatedWorldwideQuoteVersion extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Quote\WorldwideQuoteVersion|\App\Http\Resources\WorldwideQuote\NewlyCreatedWorldwideQuoteVersion $this */

        return [
            'id' => $this->getKey(),
            'worldwide_quote_id' => $this->worldwide_quote_id,
            'version_name' => sprintf('%s %s %s', $this->user->first_name, $this->user->last_name, $this->user_version_sequence_number),
            'is_active_version' => $this->worldwideQuote->active_version_id === $this->getKey(),
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}

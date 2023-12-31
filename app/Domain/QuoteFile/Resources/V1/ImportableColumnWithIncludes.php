<?php

namespace App\Domain\QuoteFile\Resources\V1;

use App\Domain\QuoteFile\Models\ImportableColumn;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportableColumnWithIncludes extends JsonResource
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
        /* @var ImportableColumn|ImportableColumnWithIncludes $this */

        return [
            'id' => $this->getKey(),
            'header' => $this->header,
            'de_header_reference' => $this->de_header_reference,
            'name' => $this->name,
            'type' => $this->type,
            'is_system' => (bool) $this->is_system,
            'country_id' => $this->country_id,
            'country' => $this->country,
            'aliases' => AliasResource::collection($this->aliases),
            'created_at' => $this->created_at?->format(config('date.format_time')),
            'activated_at' => $this->activated_at,
        ];
    }
}

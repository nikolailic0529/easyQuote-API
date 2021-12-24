<?php

namespace App\Http\Resources\ImportableColumn;

use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportableColumnList extends JsonResource
{
    public array $availableIncludes = ['aliases'];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var ImportableColumn|ImportableColumnWithIncludes $this */

        return [
            'id' => $this->getKey(),
            'header' => $this->header,
            'name' => $this->name,
            'type' => $this->type,
            'is_system' => (bool)$this->is_system,
            'country_id' => $this->country_id,
            'country' => [
                'name' => $this->country_name,
            ],
            'created_at' => $this->created_at?->format(config('date.format_time')),
            'activated_at' => $this->activated_at,
        ];
    }
}

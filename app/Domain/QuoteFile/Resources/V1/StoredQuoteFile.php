<?php

namespace App\Domain\QuoteFile\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class StoredQuoteFile extends JsonResource
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
        return [
            'id' => $this->id,
            'original_file_path' => $this->original_file_path,
            'original_file_name' => $this->original_file_name,
            'format' => $this->format,
            'imported_page' => $this->imported_page,
            'pages' => (int) $this->pages,
            'created_at' => $this->created_at,
        ];
    }
}

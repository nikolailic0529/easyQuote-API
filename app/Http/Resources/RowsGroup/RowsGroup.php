<?php

namespace App\Http\Resources\RowsGroup;

use App\Http\Resources\WorldwideQuote\ContractAsset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

class RowsGroup extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'worldwide_distribution_id' => $this->worldwide_distribution_id,

            'group_name' => $this->group_name,
            'search_text' => $this->search_text,

            'rows' => ContractAsset::collection($this->whenLoaded('rows', function () {

                /** @var \App\Models\QuoteFile\DistributionRowsGroup $this */

                return tap($this->rows, function (Collection $rows) {

                    /** @var \App\Models\QuoteFile\MappedRow[] $rows */

                    foreach ($rows as $row) {

                       $row->setAttribute('is_selected', true);

                   }

                });

            })),

//            'rows_sum' => number_format((float) $this->rows_sum, 2, '.', ''),
            'rows_sum' => (float)$this->rows_sum,
            'rows_count' => $this->rows_count,

            'is_selected' => (bool)$this->is_selected,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

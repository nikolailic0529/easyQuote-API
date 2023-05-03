<?php

namespace App\Domain\SalesUnit\Resources\V1;

use App\Domain\SalesUnit\Models\SalesUnit;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesUnitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /* @var SalesUnit|self $this */

        return [
            'id' => $this->getKey(),
            'unit_name' => $this->unit_name,
            'is_default' => (bool) $this->is_default,
            'is_enabled' => (bool) $this->is_enabled,
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}

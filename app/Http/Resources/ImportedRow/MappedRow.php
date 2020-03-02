<?php

namespace App\Http\Resources\ImportedRow;

use Illuminate\Http\Resources\Json\JsonResource;
use Arr;

class MappedRow extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $keys = (array) $this->resource;

        return [
            'id'                        => $this->id,
            'is_selected'               => (bool) $this->is_selected,
            'group_name'                => $this->when(Arr::has($keys, 'group_name'), fn () => $this->group_name),
            'product_no'                => $this->when(Arr::has($keys, 'product_no'), fn () => blank($this->product_no) ? ND_01 : $this->product_no),
            'serial_no'                 => $this->when(Arr::has($keys, 'serial_no'), fn () => blank($this->serial_no) ? ND_01 : $this->serial_no),
            'description'               => $this->when(Arr::has($keys, 'description'), fn () => blank($this->description) ? ND_01 : $this->description),
            'date_from'                 => $this->when(Arr::has($keys, 'date_from'), fn () => $this->date_from),
            'date_to'                   => $this->when(Arr::has($keys, 'date_to'), fn () => $this->date_to),
            'qty'                       => $this->when(Arr::has($keys, 'qty'), fn () => (int) (blank($this->qty) ? 1 : $this->qty)),
            'price'                     => $this->when(Arr::has($keys, 'price'), fn () => $this->price),
            'system_handle'             => $this->when(Arr::has($keys, 'system_handle'), fn () => blank($this->system_handle) ? ND_01 : $this->system_handle),
            'searchable'                => $this->when(Arr::has($keys, 'searchable'), fn () => blank($this->searchable) ? ND_01 : $this->searchable),
            'service_level_description' => $this->when(Arr::has($keys, 'service_level_description'), fn () => $this->service_level_description),
        ];
    }
}

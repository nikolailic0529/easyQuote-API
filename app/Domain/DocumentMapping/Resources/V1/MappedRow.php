<?php

namespace App\Domain\DocumentMapping\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Enumerable;

class MappedRow extends JsonResource
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
        if (isset($this->rows)) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'total_count' => $this->when(isset($this['total_count']), $this->total_count),
                'total_price' => $this->when(isset($this['total_price']), $this->total_price),
                'headers_count' => $this->when(isset($this['headers_count']), $this->headers_count),
                'rows' => static::collection($this['rows']),
            ];
        }

        $row = (array) $this->resource;

        return [
            'id' => $this->when(Arr::has($row, 'id'), fn () => $this->id),
            'is_selected' => $this->when(Arr::has($row, 'is_selected'), fn () => (bool) $this->is_selected),
            'group_name' => $this->when(Arr::has($row, 'group_name'), fn () => $this->group_name),
            'product_no' => $this->when(Arr::has($row, 'product_no'), fn () => blank($this->product_no) ? ND_01 : $this->product_no),
            'serial_no' => $this->when(Arr::has($row, 'serial_no'), fn () => blank($this->serial_no) ? ND_01 : $this->serial_no),
            'description' => $this->when(Arr::has($row, 'description'), fn () => blank($this->description) ? ND_01 : $this->description),
            'date_from' => $this->when(Arr::has($row, 'date_from'), fn () => format('date', $this->date_from, country: Arr::get($row, 'country_iso_3166_2'), fromFormat: 'd/m/Y')),
            'date_to' => $this->when(Arr::has($row, 'date_to'), fn () => format('date', $this->date_to, country: Arr::get($row, 'country_iso_3166_2'), fromFormat: 'd/m/Y')),
            'qty' => $this->when(Arr::has($row, 'qty'), fn () => (int) (blank($this->qty) ? 1 : $this->qty)),
            'price' => $this->when(Arr::has($row, 'price'), fn () => $this->price),
            'system_handle' => $this->when(Arr::has($row, 'system_handle'), fn () => blank($this->system_handle) ? ND_01 : $this->system_handle),
            'searchable' => $this->when(Arr::has($row, 'searchable'), fn () => blank($this->searchable) ? ND_01 : $this->searchable),
            'service_level_description' => $this->when(Arr::has($row, 'service_level_description'), fn () => $this->service_level_description),
        ];
    }

    /**
     * Dynamically get properties from the underlying resource.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return data_get($this->resource, $key);
    }

    /**
     * Determine if an attribute exists on the resource.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        if ($this->resource instanceof Enumerable) {
            return isset($this->resource[$key]);
        }

        return isset($this->resource->{$key});
    }
}

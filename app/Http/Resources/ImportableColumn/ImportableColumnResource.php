<?php

namespace App\Http\Resources\ImportableColumn;

use App\Http\Resources\Country\CountryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportableColumnResource extends JsonResource
{
    public $availableIncludes = ['aliases'];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'header'        => $this->header,
            'name'          => $this->name,
            'type'          => $this->type,
            'is_system'     => (bool) $this->is_system,
            'country_id'    => $this->country_id,
            'country'       => $this->whenLoaded('country', $this->country->only('name')),
            'aliases'       => AliasResource::collection($this->whenLoaded('aliases')),
            'created_at'    => $this->created_at,
            'activated_at'  => $this->activated_at
        ];
    }
}

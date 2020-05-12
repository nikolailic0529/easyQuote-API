<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return ['id' => $this->id, 'country_code' => $this->iso_3166_2, 'name' => $this->name];
    }
}

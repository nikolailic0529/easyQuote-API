<?php

namespace App\Domain\Country\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryList extends JsonResource
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
        /* @var \App\Domain\Country\Models\Country|\App\Http\Resources\Country\CountryList $this */

        return [
            'id' => $this->getKey(),
            'default_currency_id' => $this->default_currency_id,
            'country_code' => $this->iso_3166_2,
            'name' => $this->name,
        ];
    }
}

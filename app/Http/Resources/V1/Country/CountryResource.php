<?php

namespace App\Http\Resources\V1\Country;

use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'iso_3166_2'            => $this->iso_3166_2,
            'is_system'             => (bool) $this->is_system,
            'default_currency_id'   => $this->default_currency_id,
            'currency_name'         => $this->currency_name,
            'currency_symbol'       => $this->currency_symbol,
            'currency_code'         => $this->currency_code,
            'default_currency'      => $this->whenLoaded('defaultCurrency'),
            'created_at'            => $this->created_at,
            'activated_at'          => $this->activated_at
        ];
    }
}

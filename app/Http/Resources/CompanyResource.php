<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'id'        => $this->id,
            'name'      => $this->name,
            'vat'       => $this->vat,
            'type'      => $this->type,
            'category'  => $this->category,
            'email'     => $this->email,
            'phone'     => $this->phone,
            'website'   => $this->website,
            'logo'      => $this->logo
        ];
    }
}

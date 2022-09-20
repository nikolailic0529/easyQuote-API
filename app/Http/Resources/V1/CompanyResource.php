<?php

namespace App\Http\Resources\V1;

use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
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
            'categories' => $this->categories->pluck('name'),
            'email'     => $this->email,
            'phone'     => $this->phone,
            'website'   => $this->website,
            'logo'      => $this->logo,
        ];
    }
}

<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Resources\Json\JsonResource;

class ExternalCompanyList extends JsonResource
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

            'name' => $this->name,
            'type' => $this->type,
            'category' => $this->category,

            'source' => $this->source,
            'source_long' => __($this->source),

            'vat' => $this->vat,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,

            'created_at' => $this->created_at,
            'activated_at' => $this->activated_at,
        ];
    }
}

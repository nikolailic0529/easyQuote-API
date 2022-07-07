<?php

namespace App\Http\Resources\V1\TemplateRepository;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResourceListing extends JsonResource
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
            'id'    => $this->id,
            'name'  => $this->name
        ];
    }
}

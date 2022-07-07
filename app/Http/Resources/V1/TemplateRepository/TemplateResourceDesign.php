<?php

namespace App\Http\Resources\V1\TemplateRepository;

use Illuminate\Http\Resources\Json\JsonResource;

class TemplateResourceDesign extends JsonResource
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
            'id'                => $this->id,
            'template_fields'   => $this->template_fields,
            'currency'          => $this->currency,
            'form_data'         => $this->form_data,
            'data_headers'      => $this->data_headers
        ];
    }
}

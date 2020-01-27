<?php

namespace App\Http\Resources\TemplateRepository;

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
        $this->loadMissing('templateFields.templateFieldType');

        return [
            'id'                => $this->id,
            'template_fields'   => $this->templateFields,
            'currency'          => $this->currency,
            'form_data'         => $this->form_data,
            'data_headers'      => $this->data_headers
        ];
    }
}

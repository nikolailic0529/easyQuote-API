<?php

namespace App\Http\Resources\TemplateRepository;
use App\Contracts\Repositories\QuoteTemplate\TemplateFieldRepositoryInterface as TemplateFields;

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
            'template_fields'   => app(TemplateFields::class)->allSystem(),
            'currency'          => $this->currency,
            'form_data'         => $this->form_data,
            'data_headers'      => $this->data_headers
        ];
    }
}

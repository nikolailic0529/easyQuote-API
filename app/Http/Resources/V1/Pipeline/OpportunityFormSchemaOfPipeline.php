<?php

namespace App\Http\Resources\V1\Pipeline;

use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityFormSchemaOfPipeline extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\Pipeline\Pipeline|\App\Http\Resources\Pipeline\OpportunityFormSchemaOfPipeline $this */

        return [
            'form_data' => transform($this->opportunityForm, function (\App\Models\OpportunityForm\OpportunityForm $opportunityForm) {
                return $opportunityForm->formSchema->form_data;
            }, [])
        ];
    }
}

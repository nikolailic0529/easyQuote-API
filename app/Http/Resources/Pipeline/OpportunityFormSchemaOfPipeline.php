<?php

namespace App\Http\Resources\Pipeline;

use App\Models\Pipeline\OpportunityFormSchema;
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
            'form_data' => transform($this->opportunityFormSchema, function (OpportunityFormSchema $opportunityFormSchema) {
                return $opportunityFormSchema->form_data;
            }, [])
        ];
    }
}

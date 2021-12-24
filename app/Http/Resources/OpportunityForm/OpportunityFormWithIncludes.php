<?php

namespace App\Http\Resources\OpportunityForm;

use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityFormWithIncludes extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\OpportunityForm\OpportunityForm|\App\Http\Resources\OpportunityForm\OpportunityFormWithIncludes $this */
        return [
            'id' => $this->getKey(),
            'space_id' => $this->pipeline->space_id,
            'pipeline_id' => $this->pipeline_id,
            'pipeline' => $this->pipeline,
            'form_data' => $this->formSchema->form_data ?? [],
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}

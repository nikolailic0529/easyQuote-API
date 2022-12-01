<?php

namespace App\Http\Resources\V1\OpportunityForm;

use App\Models\OpportunityForm\OpportunityForm;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OpportunityForm
 */
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
        return [
            'id' => $this->getKey(),
            'space_id' => $this->pipeline->space_id,
            'pipeline_id' => $this->pipeline_id,
            'pipeline' => $this->pipeline,
            'form_data' => $this->formSchema->form_data ?? [],
            'is_system' => (bool)$this->is_system,
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}

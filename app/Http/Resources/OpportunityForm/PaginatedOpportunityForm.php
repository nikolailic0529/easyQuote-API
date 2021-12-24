<?php

namespace App\Http\Resources\OpportunityForm;

use Illuminate\Http\Resources\Json\JsonResource;

class PaginatedOpportunityForm extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\OpportunityForm\OpportunityForm|\App\Http\Resources\OpportunityForm\PaginatedOpportunityForm $this */

        /** @var \App\Models\User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'space_name' => $this->space_name,
            'pipeline_name' => $this->pipeline_name,
            'is_system' => (bool)$this->is_system,
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}

<?php

namespace App\Domain\Pipeline\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PaginatedPipeline extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Domain\Pipeline\Models\Pipeline|\App\Http\Resources\Pipeline\PaginatedPipeline $this */

        /** @var \App\Domain\User\Models\User $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'space_name' => $this->space_name,
            'is_system' => (bool) $this->is_system,
            'is_default' => (bool) $this->is_default,
            'pipeline_name' => $this->pipeline_name,

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

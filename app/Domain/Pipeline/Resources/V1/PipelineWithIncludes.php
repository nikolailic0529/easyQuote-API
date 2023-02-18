<?php

namespace App\Domain\Pipeline\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PipelineWithIncludes extends JsonResource
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
        /* @var \App\Domain\Pipeline\Models\Pipeline|\App\Http\Resources\Pipeline\PipelineWithIncludes $this */

        return [
            'id' => $this->getKey(),
            'space_id' => $this->space_id,
            'space_name' => $this->space->space_name,
            'pipeline_name' => $this->pipeline_name,
            'pipeline_stages' => value(function () {
                /* @var \App\Domain\Pipeline\Models\Pipeline|\App\Http\Resources\Pipeline\PipelineWithIncludes $this */
                return $this->pipelineStages
                    ->sortBy('stage_order')
                    ->append('qualified_stage_name')
                    ->values();
            }),
            'is_default' => (bool) $this->is_default,
            'is_system' => (bool) $this->is_system,
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}

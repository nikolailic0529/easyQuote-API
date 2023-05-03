<?php

namespace App\Domain\Authorization\Resources\V1;

use App\Domain\Authorization\Models\Role;
use App\Domain\Pipeline\Models\Pipeline;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
final class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function toArray($request): array
    {
        $allowedOpportunityPipelines = $this->allowedOpportunityPipelines->map(static function (Pipeline $pipeline): array {
            return [
                'pipeline_id' => $pipeline->getKey(),
                'pipeline_name' => $pipeline->pipeline_name,
            ];
        });

        return [
            'id' => $this->getKey(),
            'user_id' => $this->user()->getParentKey(),
            'name' => $this->name,
            'is_system' => (bool) $this->is_system,
            'access_data' => [
                ...$this->access->toArray(),
                'allowed_opportunity_pipelines' => $allowedOpportunityPipelines->toArray(),
            ],
            $this->merge($this->additional),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'activated_at' => $this->activated_at,
        ];
    }
}

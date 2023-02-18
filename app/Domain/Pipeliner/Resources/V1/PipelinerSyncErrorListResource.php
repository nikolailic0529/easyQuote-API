<?php

namespace App\Domain\Pipeliner\Resources\V1;

use App\Domain\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\Pipeliner\Models\PipelinerSyncError;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PipelinerSyncError
 */
class PipelinerSyncErrorListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),
            'entity_id' => $this->entity()->getParentKey(),
            'entity_type' => class_basename($this->entity),
            'entity_name' => $this->entity instanceof ProvidesIdForHumans
                ? $this->entity->getIdForHumans()
                : $this->entity()->getParentKey(),
            'error_message' => $this->error_message,
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
            'archived_at' => $this->archived_at,
            'resolved_at' => $this->resolved_at,
        ];
    }
}

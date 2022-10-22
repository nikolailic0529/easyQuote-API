<?php

namespace App\Http\Resources\Pipeliner;

use App\Contracts\ProvidesIdForHumans;
use App\Models\Pipeliner\PipelinerSyncError;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PipelinerSyncError
 */
class PipelinerSyncErrorListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
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
        ];
    }
}

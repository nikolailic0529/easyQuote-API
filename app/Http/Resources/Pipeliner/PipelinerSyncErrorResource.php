<?php

namespace App\Http\Resources\Pipeliner;

use App\Contracts\ProvidesIdForHumans;
use App\Models\Pipeliner\PipelinerSyncError;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PipelinerSyncError
 */
class PipelinerSyncErrorResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->getKey(),
            'entity_id' => $this->entity()->getParentKey(),
            'entity_type' => $this->entity()->getMorphType(),
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

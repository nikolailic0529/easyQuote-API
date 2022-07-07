<?php

namespace App\Http\Resources\V2\CustomField;

use App\Models\System\CustomFieldValue;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomFieldValueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var CustomFieldValue|self $this */

        return [
            'id' => $this->getKey(),
            'field_value' => $this->field_value,
            'allowed_by' => $this->allowedByRelations->pluck('allowed_by_id'),
            'entity_order' => $this->entity_order,
            'is_default' => $this->is_default,
        ];
    }
}

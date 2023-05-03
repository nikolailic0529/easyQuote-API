<?php

namespace App\Domain\CustomField\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomFieldWithValuesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /* @var \App\Domain\CustomField\Models\CustomField|self $this */

        return [
            'id' => $this->getKey(),
            'parent_field_id' => $this->parentField()->getParentKey(),
            'parent_field_name' => $this->parentField?->field_name,
            'field_name' => $this->field_name,
            'field_values' => CustomFieldValueResource::collection($this->values),
        ];
    }
}

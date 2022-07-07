<?php

namespace App\Http\Resources\V2\CustomField;

use App\Models\System\CustomField;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomFieldWithValuesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var CustomField|self $this */

        return [
            'id' => $this->getKey(),
            'parent_field_id' => $this->parentField()->getParentKey(),
            'parent_field_name' => $this->parentField?->field_name,
            'field_name' => $this->field_name,
            'field_values' => CustomFieldValueResource::collection($this->values),
        ];
    }
}

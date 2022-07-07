<?php

namespace App\Http\Resources\V1\Setting;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->value,
            'label' => $this->label,
            'field_title' => $this->field_title,
            'field_type' => $this->field_type,
        ];
    }
}

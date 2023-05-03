<?php

namespace App\Domain\Settings\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
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

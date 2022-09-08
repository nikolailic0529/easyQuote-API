<?php

namespace App\Http\Resources\V1\Setting;

use App\Models\System\SystemSetting;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SystemSetting
 */
class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->getKey(),
            'key' => $this->key,
            'value' => $this->value,
            'possible_values' => $this->possible_values,
            'is_read_only' => (bool)$this->is_read_only,
            'validation' => $this->validation,
            'label' => $this->label,
            'field_title' => $this->field_title,
            'field_type' => $this->field_type,
        ];
    }
}

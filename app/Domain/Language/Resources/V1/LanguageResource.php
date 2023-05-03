<?php

namespace App\Domain\Language\Resources\V1;

use App\Domain\Language\Models\Language;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Language
 */
class LanguageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),
            'code' => $this->code,
            'name' => $this->name,
        ];
    }
}

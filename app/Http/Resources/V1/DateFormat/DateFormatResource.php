<?php

namespace App\Http\Resources\V1\DateFormat;

use Illuminate\Http\Resources\Json\JsonResource;

class DateFormatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var \UnitEnum|self $this */

        return [
            'name' => $this->name,
            'value' => $this->value,
        ];
    }
}

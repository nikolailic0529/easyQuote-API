<?php

namespace App\Domain\User\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class AttemptsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'attempts' => optional($this)->failed_attempts ?? 0,
        ];
    }
}

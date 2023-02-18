<?php

namespace App\Domain\Rescue\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerRepositoryResource extends JsonResource
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
        /* @var self|\App\Domain\Rescue\Models\Customer $this */

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'rfq' => $this->rfq,
            'valid_until' => \format('date', $this->valid_until),
            'support_start' => \format('date', $this->support_start),
            'support_end' => \format('date', $this->support_end),
            'created_at' => $this->created_at?->format(config('date.format_time')),
        ];
    }
}

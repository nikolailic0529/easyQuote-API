<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Arr;

class QuoteVersionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->usingVersion->loadDefaultRelations()->withAppends()->toArray();

        Arr::set($resource, 'id', $this->id);
        Arr::set($resource, 'versions_selection', $this->versions_selection);

        return $resource;
    }
}

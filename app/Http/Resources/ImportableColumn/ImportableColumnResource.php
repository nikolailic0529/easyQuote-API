<?php

namespace App\Http\Resources\ImportableColumn;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportableColumnResource extends JsonResource
{
    public $availableIncludes = ['aliases'];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'header'        => $this->header,
            'name'          => $this->name,
            'is_system'     => (bool) $this->is_system,
            'aliases'       => AliasResource::collection($this->whenLoaded('aliases')),
            'created_at'    => $this->created_at,
            'activated_at'  => $this->activated_at
        ];
    }
}

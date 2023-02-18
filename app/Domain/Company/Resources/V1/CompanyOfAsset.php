<?php

namespace App\Domain\Company\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class CompanyOfAsset extends JsonResource
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
        /** @var \App\Domain\Company\Models\Company|CompanyOfAsset $this */

        /** @var \App\Domain\User\Models\User|null $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'source' => $this->source,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,

            'permissions' => [
                'view' => $user?->can('view', $this->resource),
                'update' => $user?->can('update', $this->resource),
                'delete' => $user?->can('delete', $this->resource),
            ],

            'created_at' => $this->{$this->getCreatedAtColumn()},
        ];
    }
}

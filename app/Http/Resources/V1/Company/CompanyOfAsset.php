<?php

namespace App\Http\Resources\V1\Company;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyOfAsset extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Company|CompanyOfAsset $this */

        /** @var User|null $user */
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

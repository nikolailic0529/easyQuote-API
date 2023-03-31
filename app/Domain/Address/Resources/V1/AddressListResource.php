<?php

namespace App\Domain\Address\Resources\V1;

use App\Domain\Address\Models\Address;
use App\Domain\User\Resources\V1\UserRelationResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Address
 */
class AddressListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),

            'user_id' => $this->user()->getParentKey(),
            'user' => UserRelationResource::make($this->user),

            'country_id' => $this->country()->getParentKey(),
            'country' => $this->country,

            'address_type' => $this->address_type,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'city' => $this->city,
            'state' => $this->state,
            'state_code' => $this->state_code,
            'post_code' => $this->post_code,

            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
            'activated_at' => $this->activated_at,
        ];
    }
}

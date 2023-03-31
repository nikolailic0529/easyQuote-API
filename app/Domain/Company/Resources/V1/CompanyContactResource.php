<?php

namespace App\Domain\Company\Resources\V1;

use App\Domain\Contact\Models\Contact;
use App\Domain\User\Resources\V1\UserRelationResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contact
 */
class CompanyContactResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->getKey(),

            'user_id' => $this->user()->getParentKey(),
            'user' => UserRelationResource::make($this->user),

            'sales_unit_id' => $this->salesUnit()->getParentKey(),
            'address_id' => $this->address()->getParentKey(),
            'language_id' => $this->language()->getParentKey(),

            'contact_type' => $this->contact_type,
            'gender' => $this->gender,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'job_title' => $this->job_title,
            'is_verified' => (bool) $this->is_verified,
            'is_default' => (bool) $this->pivot->is_default,

            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
            'activated_at' => $this->activated_at,
        ];
    }
}

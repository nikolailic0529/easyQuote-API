<?php

namespace App\Http\Resources\V1\User;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRelationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var User|self $this */

        return [
            'id' => $this->getKey(),
            'email' => $this->email,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'user_fullname' => $this->user_fullname,
        ];
    }
}

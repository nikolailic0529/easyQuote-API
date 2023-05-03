<?php

namespace App\Domain\User\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class UserForm extends JsonResource
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
        return $this->form ?? config('user_form.defaults.'.$this->key);
    }
}

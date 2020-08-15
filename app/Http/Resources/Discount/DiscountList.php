<?php

namespace App\Http\Resources\Discount;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var \App\Models\User */
        $user = $request->user();

        $array = parent::toArray($request);

        return array_merge($array, [
            'permissions' => [
                'view'      => $user->can('view', $this->resource),
                'update'    => $user->can('update', $this->resource),
                'delete'    => $user->can('delete', $this->resource),
            ],
        ]);
    }
}

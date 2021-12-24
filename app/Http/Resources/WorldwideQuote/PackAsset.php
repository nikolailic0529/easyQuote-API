<?php

namespace App\Http\Resources\WorldwideQuote;

use App\Models\User;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Http\Resources\Json\JsonResource;

class PackAsset extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var PackAsset|WorldwideQuoteAsset $this */

        /** @var User $user */
        $user = $request->user();

        $array = parent::toArray($request);

        return array_merge($array, [
            'owned_by_customer' => $this->when(!is_null($this->owned_by_customer), function () use ($user) {
                /** @var PackAsset|WorldwideQuoteAsset $this */

                return [
                    'id' => $this->owned_by_customer->getKey(),
                    'name' => $this->owned_by_customer->name,
                    'permissions' => [
                        'view' => $user->can('view', $this->owned_by_customer),
                        'update' => $user->can('update', $this->owned_by_customer),
                        'delete' => $user->can('delete', $this->owned_by_customer),
                    ],
                ];
            }),
        ]);
    }
}

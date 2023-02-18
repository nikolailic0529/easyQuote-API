<?php

namespace App\Domain\Worldwide\Resources\V1\Quote;

use App\Domain\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractAsset extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var ContractAsset|\App\Domain\DocumentMapping\Models\MappedRow $this */

        /** @var User $user */
        $user = $request->user();

        $array = parent::toArray($request);

        return array_merge($array, [
            'owned_by_customer' => $this->when(!is_null($this->owned_by_customer), function () use ($user) {
                /* @var ContractAsset|\App\Domain\DocumentMapping\Models\MappedRow $this */

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

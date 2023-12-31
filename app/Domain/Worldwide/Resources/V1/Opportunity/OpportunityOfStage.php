<?php

namespace App\Domain\Worldwide\Resources\V1\Opportunity;

use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityOfStage extends JsonResource
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
        /** @var Opportunity|self $this */

        /** @var User $user */
        $user = $request->user();

        $this->primaryAccount?->append('logo');
        $this->endUser?->append('logo');

        return array_merge(parent::toArray($request), [
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
        ]);
    }
}

<?php

namespace App\Http\Resources\V1\Opportunity;

use App\DTO\Opportunity\PipelineStageOpportunitiesData;
use App\Models\User;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GroupedOpportunityCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var User $user */
        $user = $request->user();

        $this->resource
            ->each(static function (PipelineStageOpportunitiesData $stage) use ($request, $user) {

                foreach ($stage->opportunities as $opportunity) {
                    $opportunity->setAttribute('permissions', [
                        'view' => $user->can('view', $opportunity),
                        'update' => $user->can('update', $opportunity),
                        'delete' => $user->can('delete', $opportunity),
                    ]);

                    $opportunity->primaryAccount?->append('logo');
                    $opportunity->endUser?->append('logo');
                }

            });

        return parent::toArray($request);
    }
}

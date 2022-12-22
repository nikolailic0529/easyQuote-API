<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserAssignedToModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AllocationGate
{
    public function isAssignedTo(Model $model, User $user): bool
    {
        /** @var Collection $assignedModelsToUser */
        return once(static function () use ($model, $user): bool {
            return $user->assignedToModelRelations
                ->lazy()
                ->whereStrict('model_id', $model->getKey())
                ->whereStrict('model_type', $model->getMorphClass())
                ->filter(static function (UserAssignedToModel $relation): bool {
                    return null === $relation->assignment_start_date || $relation->assignment_start_date->lessThanOrEqualTo(today());
                })
                ->filter(static function (UserAssignedToModel $relation): bool {
                    return null === $relation->assignment_end_date || $relation->assignment_end_date->greaterThanOrEqualTo(today());
                })
                ->isNotEmpty();
        });
    }
}
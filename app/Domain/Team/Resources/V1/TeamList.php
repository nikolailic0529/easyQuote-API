<?php

namespace App\Domain\Team\Resources\V1;

use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Team\Models\Team
 */
class TeamList extends JsonResource
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
        return [
            'id' => $this->getKey(),
            'team_name' => $this->team_name,
            'business_division_name' => $this->business_division_name,
            'sales_units' => $this->salesUnits->map(static function (SalesUnit $unit): array {
                return [
                    'id' => $unit->getKey(),
                    'unit_name' => $unit->unit_name,
                ];
            }),
            'team_leaders' => $this->teamLeaders->map(static function (User $user): array {
                return [
                    'id' => $user->getKey(),
                    'user_fullname' => $user->user_fullname,
                    'email' => $user->email,
                ];
            }),
            'monthly_goal_amount' => $this->monthly_goal_amount,
            'is_system' => (bool) $this->is_system,
            'created_at' => $this->{$this->getCreatedAtColumn()},
        ];
    }
}

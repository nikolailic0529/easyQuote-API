<?php

namespace App\Http\Resources\V1\Team;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamWithIncludes extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Team|TeamWithIncludes $this */

        return [
            'id' => $this->getKey(),
            'team_name' => $this->team_name,
            'business_division_id' => $this->business_division_id,
            'monthly_goal_amount' => $this->monthly_goal_amount,

            'team_leaders' => value(function () {
                /** @var Team $this */

                $this->loadMissing(['teamLeaders:id,first_name,last_name,email,user_fullname', 'teamLeaders.image']);

                return $this->teamLeaders->makeHidden('pivot')->map(function (User $user) {
                    return [
                        'id' => $user->getKey(),
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'user_fullname' => $user->user_fullname,
                        'picture' => $user->picture,
                    ];
                });
            }),

            'sales_units' => $this->salesUnits,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

<?php

namespace App\Http\Requests\Activity;

use App\Http\Resources\UserListResource;
use App\Models\System\Period;
use App\Queries\UserQueries;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class ShowActivityMeta extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function getActivityMetaData(): array
    {
        $users = with($this->container[UserQueries::class]->usersListQuery()->get(), function (Collection $users) {
            return UserListResource::collection($users);
        });

        $periods = array_map(function (string $period) {
            $label = Period::create($period)->label;
            return [
                'label' => $label,
                'value' => $period
            ];
        }, config('activitylog.periods'));

        $types = array_map(function (string $type) {
            return [
                'label' => __('activitylog.types.'.$type),
                'value' => $type
            ];
        }, config('activitylog.types'));

        $subjectTypes = array_map(function (string $subjectType) {
            $label = ucwords(str_replace(['-', '_'], ' ', $subjectType));

            return [
                'label' => $label,
                'value' => $subjectType
            ];
        }, array_keys(config('activitylog.subject_types')));

        return [
            'users' => $users,
            'periods' => $periods,
            'types' => $types,
            'subject_types' => $subjectTypes
        ];
    }
}

<?php

namespace App\Http\Requests\Activity;

use App\Http\Resources\UserListResource;
use App\Queries\UserQueries;
use App\Services\Activity\ActivityDataMapper;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ShowActivityLogMetaData extends FormRequest
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

    public function getMetaData(): array
    {
        $config = $this->container[Config::class];
        /** @var UserQueries $userQueries */
        $userQueries = $this->container[UserQueries::class];

        $periods = collect($config->get('activitylog.periods'))->transform(function ($value) {
            $label = Carbon::period($value)->label;
            return compact('label', 'value');
        })->all();

        $types = collect($config->get('activitylog.types'))->transform(function ($value) {
            $label = __('activitylog.types.'.$value);
            return compact('label', 'value');
        })->all();

        $subjectTypes = collect($config->get('activitylog.subject_types'))->keys()->transform(function ($value) {
            $label = ActivityDataMapper::resolveNameOfAttribute($value);
            return compact('label', 'value');
        })->all();

        $users = UserListResource::collection($userQueries->userListQuery()->get());

        return [
            'periods' => $periods,
            'types' => $types,
            'subject_types' => $subjectTypes,
            'users' => $users
        ];
    }
}

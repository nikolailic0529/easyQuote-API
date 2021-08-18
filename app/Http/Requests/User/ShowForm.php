<?php

namespace App\Http\Requests\User;

use App\Contracts\Repositories\CountryRepositoryInterface;
use App\Contracts\Repositories\RoleRepositoryInterface;
use App\Queries\TimezoneQueries;
use Illuminate\Foundation\Http\FormRequest;

class ShowForm extends FormRequest
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

    public function data(): array
    {
        /** @var RoleRepositoryInterface */
        $roles = app(RoleRepositoryInterface::class);

        /** @var TimezoneQueries $timezoneQueries */
        $timezoneQueries = $this->container[TimezoneQueries::class];

        /** @var CountryRepositoryInterface */
        $countries = app(CountryRepositoryInterface::class);

        $filteredRoles = $roles->allActivated(['id', 'name'])
            ->sortBy('name', SORT_NATURAL)
            ->values();

        return [
            'roles' => $filteredRoles,
            'countries' => $countries->all(),
            'timezones' => $timezoneQueries->listOfTimezonesQuery()->get(),
        ];
    }
}

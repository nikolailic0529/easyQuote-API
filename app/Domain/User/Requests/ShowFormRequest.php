<?php

namespace App\Domain\User\Requests;

use App\Domain\Authorization\Contracts\RoleRepositoryInterface;
use App\Domain\Country\Contracts\CountryRepositoryInterface;
use App\Domain\Timezone\Queries\TimezoneQueries;
use Illuminate\Foundation\Http\FormRequest;

class ShowFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }

    public function data(): array
    {
        /** @var \App\Domain\Authorization\Contracts\RoleRepositoryInterface */
        $roles = app(RoleRepositoryInterface::class);

        /** @var \App\Domain\Timezone\Queries\TimezoneQueries $timezoneQueries */
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

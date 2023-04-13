<?php

namespace App\Domain\User\Requests;

use App\Domain\Authorization\Queries\RoleQueries;
use App\Domain\Country\Contracts\CountryRepositoryInterface;
use App\Domain\Timezone\Queries\TimezoneQueries;
use Illuminate\Foundation\Http\FormRequest;

class ShowFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
        ];
    }

    public function data(): array
    {
        /** @var RoleQueries $roleQueries */
        $roleQueries = $this->container->make(RoleQueries::class);

        /** @var TimezoneQueries $timezoneQueries */
        $timezoneQueries = $this->container[TimezoneQueries::class];

        /** @var CountryRepositoryInterface $countries */
        $countries = app(CountryRepositoryInterface::class);

        $roles = $roleQueries->activeRoles()->get()
            ->sortBy('name', SORT_NATURAL)
            ->values();

        return [
            'roles' => $roles,
            'countries' => $countries->all(),
            'timezones' => $timezoneQueries->listOfTimezonesQuery()->get(),
        ];
    }
}

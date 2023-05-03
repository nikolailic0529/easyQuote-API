<?php

namespace App\Domain\Country\Services;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Country\DataTransferObjects\CreateCountryData;
use App\Domain\Country\DataTransferObjects\UpdateCountryData;
use App\Domain\Country\Models\Country;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class CountryEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly ConnectionResolverInterface $conResolver,
    ) {
    }

    public function createCountry(CreateCountryData $data): Country
    {
        return tap(new Country(), static function (Country $country) use ($data): void {
            $country->forceFill($data->toArray());

            $country->save();
        });
    }

    public function updateCountry(Country $country, UpdateCountryData $data): Country
    {
        return tap($country, static function (Country $country) use ($data): void {
            $country->forceFill($data->toArray());

            $country->save();
        });
    }

    public function deleteCountry(Country $country): void
    {
        $country->delete();
    }

    public function markCountryAsActive(Country $country): void
    {
        $country->activated_at = now();
        $country->save();
    }

    public function markCountryAsInactive(Country $country): void
    {
        $country->activated_at = null;
        $country->save();
    }

    public function setCauser(?Model $causer): static
    {
        $this->causer = $causer;

        return $this;
    }
}

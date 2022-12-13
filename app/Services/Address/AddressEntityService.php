<?php

namespace App\Services\Address;

use App\Contracts\CauserAware;
use App\DTO\Address\CreateAddressCompanyRelationNoBackrefData;
use App\DTO\Address\CreateAddressData;
use App\DTO\Address\UpdateAddressData;
use App\Events\Address\AddressCreated;
use App\Events\Address\AddressDeleted;
use App\Events\Address\AddressUpdated;
use App\Models\Address;
use App\Models\Company;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use Spatie\LaravelData\DataCollection;
use Webpatser\Uuid\Uuid;

class AddressEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected ConnectionInterface $connection,
        protected EventDispatcher $eventDispatcher
    ) {
    }

    public function createAddress(CreateAddressData $data): Address
    {
        return tap(new Address(), function (Address $address) use ($data) {
            $address->{$address->getKeyName()} = (string) Uuid::generate(4);

            $address->user()->associate($this->causer);
            $address->forceFill($data->except('company_relations')->all());

            $companyRelations = collect();

            if ($data->company_relations instanceof DataCollection) {
                $companyRelations = $this->mapCompanyRelations($data->company_relations);
            }

            $this->connection->transaction(static function () use ($companyRelations, $address): void {
                $address->save();
                $address->companies()->syncWithoutDetaching($companyRelations->all());

                $address->companies()
                    ->where('updated_at', '<', $address->{$address->getUpdatedAtColumn()})
                    ->get()
                    ->each
                    ->touch();
            });

            $this->eventDispatcher->dispatch(
                new AddressCreated(address: $address, causer: $this->causer)
            );
        });
    }

    public function updateAddress(Address $address, UpdateAddressData $data): Address
    {
        return tap($address, function (Address $address) use ($data) {
            $oldAddress = (new Address())->setRawAttributes($address->getRawOriginal());

            $address->forceFill($data->except('company_relations')->all());

            $companyRelations = collect();

            if ($data->company_relations instanceof DataCollection) {
                $companyRelations = $this->mapCompanyRelations($data->company_relations);
            }

            $this->connection->transaction(static function () use ($companyRelations, $address): void {
                $address->save();
                $address->companies()->syncWithoutDetaching($companyRelations->all());

                $address->companies()
                    ->where('updated_at', '<', $address->{$address->getUpdatedAtColumn()})
                    ->get()
                    ->each
                    ->touch();
            });

            $this->eventDispatcher->dispatch(
                new AddressUpdated(address: $oldAddress, newAddress: $address, causer: $this->causer)
            );
        });
    }

    protected function mapCompanyRelations(DataCollection $relations): BaseCollection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $relations->toCollection()
            ->mapWithKeys(function (CreateAddressCompanyRelationNoBackrefData $data): array {
                $attributes = $data->except('id')->all();

                return [$data->id => $attributes];
            });
    }

    public function deleteAddress(Address $address): void
    {
        $this->connection->transaction(static fn() => $address->delete());

        $this->eventDispatcher->dispatch(
            new AddressDeleted($address, $this->causer)
        );
    }

    public function markAddressAsActive(Address $address): void
    {
        $address->activated_at = now();

        $this->connection->transaction(static fn() => $address->save());
    }

    public function markAddressAsInactive(Address $address): void
    {
        $address->activated_at = null;

        $this->connection->transaction(static fn() => $address->save());
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}
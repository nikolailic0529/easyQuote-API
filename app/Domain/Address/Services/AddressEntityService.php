<?php

namespace App\Domain\Address\Services;

use App\Domain\Address\DataTransferObjects\CreateAddressCompanyRelationNoBackrefData;
use App\Domain\Address\DataTransferObjects\CreateAddressData;
use App\Domain\Address\DataTransferObjects\UpdateAddressData;
use App\Domain\Address\Events\AddressCreated;
use App\Domain\Address\Events\AddressDeleted;
use App\Domain\Address\Events\AddressUpdated;
use App\Domain\Address\Models\Address;
use App\Domain\Authentication\Contracts\CauserAware;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
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
            });

            $companyRelations = $address->companies()->pluck(
                $address->companies()->getRelated()->getQualifiedKeyName()
            );

            $this->eventDispatcher->dispatch(
                new AddressCreated(
                    address: $address,
                    companyRelations: $companyRelations->all(),
                    causer: $this->causer
                )
            );
        });
    }

    public function updateAddress(Address $address, UpdateAddressData $data): Address
    {
        return tap($address, function (Address $address) use ($data) {
            $oldAddress = (new Address())->setRawAttributes($address->getRawOriginal());
            $oldCompanyRelations = $address->companies()->pluck(
                $address->companies()->getRelated()->getQualifiedKeyName()
            );

            $address->forceFill($data->except('company_relations')->all());

            $companyRelations = collect();

            if ($data->company_relations instanceof DataCollection) {
                $companyRelations = $this->mapCompanyRelations($data->company_relations);
            }

            $this->connection->transaction(static function () use ($companyRelations, $address): void {
                $address->save();
                $address->companies()->syncWithoutDetaching($companyRelations->all());
            });

            $this->eventDispatcher->dispatch(
                new AddressUpdated(
                    address: $oldAddress,
                    companyRelations: $oldCompanyRelations->all(),
                    newAddress: $address,
                    causer: $this->causer
                )
            );
        });
    }

    protected function mapCompanyRelations(DataCollection $relations): BaseCollection
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $relations->toCollection()
            ->mapWithKeys(function (CreateAddressCompanyRelationNoBackrefData $data): array {
                $attributes = $data->except('id')->all();

                return [$data->id => $attributes];
            });
    }

    public function deleteAddress(Address $address): void
    {
        $this->connection->transaction(static fn () => $address->delete());

        $this->eventDispatcher->dispatch(
            new AddressDeleted($address, $this->causer)
        );
    }

    public function markAddressAsActive(Address $address): void
    {
        $address->activated_at = now();

        $this->connection->transaction(static fn () => $address->save());
    }

    public function markAddressAsInactive(Address $address): void
    {
        $address->activated_at = null;

        $this->connection->transaction(static fn () => $address->save());
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}

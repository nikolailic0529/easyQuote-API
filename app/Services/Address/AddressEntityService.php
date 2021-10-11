<?php

namespace App\Services\Address;

use App\Contracts\CauserAware;
use App\DTO\Address\CreateAddressData;
use App\DTO\Address\UpdateAddressData;
use App\Events\Address\AddressCreated;
use App\Events\Address\AddressDeleted;
use App\Events\Address\AddressUpdated;
use App\Models\Address;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class AddressEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(protected ConnectionInterface $connection,
                                protected EventDispatcher     $eventDispatcher)
    {
    }

    public function createAddress(CreateAddressData $data): Address
    {
        return tap(new Address(), function (Address $address) use ($data) {
            $address->{$address->getKeyName()} = (string)Uuid::generate(4);
            $address->address_type = $data->address_type;
            $address->address_1 = $data->address_1;
            $address->address_2 = $data->address_2;
            $address->city = $data->city;
            $address->state = $data->state;
            $address->state_code = $data->state_code;
            $address->post_code = $data->post_code;

            $address->country()->associate($data->country_id);

            $this->connection->transaction(fn() => $address->save());

            $this->eventDispatcher->dispatch(
                new AddressCreated(address: $address, causer: $this->causer)
            );
        });
    }

    public function updateAddress(Address $address, UpdateAddressData $data): Address
    {
        return tap($address, function (Address $address) use ($data) {
            $oldAddress = (new Address())->setRawAttributes($address->getRawOriginal());

            $address->address_type = $data->address_type;
            $address->address_1 = $data->address_1;
            $address->address_2 = $data->address_2;
            $address->city = $data->city;
            $address->state = $data->state;
            $address->state_code = $data->state_code;
            $address->post_code = $data->post_code;

            $address->country()->associate($data->country_id);

            $this->connection->transaction(fn() => $address->save());

            $this->eventDispatcher->dispatch(
                new AddressUpdated(address: $oldAddress, newAddress: $address, causer: $this->causer)
            );
        });
    }

    public function deleteAddress(Address $address): void
    {
        $this->connection->transaction(fn() => $address->delete());

        $this->eventDispatcher->dispatch(
            new AddressDeleted($address, $this->causer)
        );
    }

    public function markAddressAsActive(Address $address): void
    {
        $address->activated_at = now();

        $this->connection->transaction(fn() => $address->save());
    }

    public function markAddressAsInactive(Address $address): void
    {
        $address->activated_at = null;

        $this->connection->transaction(fn() => $address->save());
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}
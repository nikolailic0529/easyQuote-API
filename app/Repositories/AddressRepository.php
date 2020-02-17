<?php

namespace App\Repositories;

use App\Contracts\Repositories\AddressRepositoryInterface;
use App\Http\Requests\Address\{
    StoreAddressRequest,
    UpdateAddressRequest
};
use App\Models\Address;
use Illuminate\Database\Eloquent\{
    Builder,
    Model
};

class AddressRepository extends SearchableRepository implements AddressRepositoryInterface
{
    /** @var \App\Models\Address */
    protected Address $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }

    public function query(): Builder
    {
        return $this->address->query()->commonTypes()->with('country');
    }

    public function find(string $id): Address
    {
        return $this->query()->whereId($id)->firstOrFail();
    }

    public function create(StoreAddressRequest $request): Address
    {
        return $this->address->create($request->validated());
    }

    public function update(UpdateAddressRequest $request, string $id): Address
    {
        return tap($this->find($id))->update($request->validated());
    }

    public function delete(string $id): bool
    {
        return $this->find($id)->delete();
    }

    public function activate(string $id): bool
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id): bool
    {
        return $this->find($id)->deactivate();
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\OrderByCountry::class,
            \App\Http\Query\Address\OrderByAddressType::class,
            \App\Http\Query\Address\OrderByCity::class,
            \App\Http\Query\Address\OrderByPostCode::class,
            \App\Http\Query\Address\OrderByState::class,
            \App\Http\Query\Address\OrderByStreetAddress::class
        ];
    }

    protected function filterableQuery()
    {
        return $this->query();
    }

    protected function searchableScope($query)
    {
        return $query->commonTypes()->with('country');
    }

    protected function searchableModel(): Model
    {
        return $this->address;
    }

    protected function searchableFields(): array
    {
        return [
            'city^5',
            'country_name^5',
            'state^4',
            'address_type^4',
            'address_1^4',
            'address_2^4',
            'state_code^3',
            'post_code^3',
            'created_at^2'
        ];
    }
}

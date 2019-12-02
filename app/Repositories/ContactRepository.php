<?php

namespace App\Repositories;

use App\Contracts\Repositories\ContactRepositoryInterface;
use App\Http\Requests\Contact\{
    StoreContactRequest,
    UpdateContactRequest
};
use App\Models\Contact;
use Illuminate\Database\Eloquent\{
    Builder,
    Model
};

class ContactRepository extends SearchableRepository implements ContactRepositoryInterface
{
    protected $address;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function query(): Builder
    {
        return $this->contact->query();
    }

    public function find(string $id): Contact
    {
        return $this->query()->whereId($id)->firstOrFail();
    }

    public function create(StoreContactRequest $request): Contact
    {
        return $this->contact->create($request->validated());
    }

    public function update(UpdateContactRequest $request, string $id): bool
    {
        return $this->find($id)->update($request->validated());
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

    protected function searchableModel(): Model
    {
        return $this->contact;
    }

    protected function searchableFields(): array
    {
        return [
            'phone',
            'created_at^2'
        ];
    }
}

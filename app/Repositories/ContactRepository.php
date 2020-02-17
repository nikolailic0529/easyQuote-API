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
    /** @var \App\Models\Contact */
    protected Contact $address;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function query(): Builder
    {
        return $this->contact->query()->withoutType();
    }

    public function find(string $id): Contact
    {
        return $this->query()->with('image')->whereId($id)->firstOrFail()->withAppends();
    }

    public function create(StoreContactRequest $request): Contact
    {
        return tap($this->contact->create($request->validated()), function (Contact $contact) use ($request) {
            $contact->createImage($request->picture, $this->imageProperties());
            $contact->load('image')->withAppends();
        });
    }

    public function update(UpdateContactRequest $request, string $id): Contact
    {
        return tap($this->find($id), function (Contact $contact) use ($request) {
            $contact->update($request->validated());
            $contact->createImage($request->picture, $this->imageProperties());
            $contact->load('image');
        });
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
            \App\Http\Query\Contact\OrderByEmail::class,
            \App\Http\Query\Contact\OrderByFirstName::class,
            \App\Http\Query\Contact\OrderByLastName::class,
            \App\Http\Query\Contact\OrderByIsVerified::class,
            \App\Http\Query\Contact\OrderByJobTitle::class,
            \App\Http\Query\Contact\OrderByMobile::class,
            \App\Http\Query\Contact\OrderByPhone::class
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
            'first_name^5',
            'last_name^5',
            'job_title^4',
            'email^4',
            'mobile^3',
            'phone^3',
            'created_at^2'
        ];
    }

    protected function imageProperties(): array
    {
        return ['width' => 240, 'height' => 240];
    }
}

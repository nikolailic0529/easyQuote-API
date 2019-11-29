<?php

namespace App\Contracts\Repositories;

use App\Http\Requests\Contact\{
    StoreContactRequest,
    UpdateContactRequest
};
use Illuminate\Database\Eloquent\Builder;
use App\Models\Contact;

interface ContactRepositoryInterface
{
    /**
     * Retrieve all the contacts.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search through all the contacts.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Initialize a new query Builder for Activities.
     *
     * @return Builder
     */
    public function query(): Builder;

    /**
     * Retrieve the specified Contact.
     *
     * @param string $id
     * @return Contact
     */
    public function find(string $id): Contact;

    /**
     * Store a new Contact.
     *
     * @param StoreContactRequest $request
     * @return Contact
     */
    public function create(StoreContactRequest $request): Contact;

    /**
     * Update the specified Contact.
     *
     * @param UpdateContactRequest $request
     * @param string $id
     * @return bool
     */
    public function update(UpdateContactRequest $request, string $id): bool;

    /**
     * Delete the specified Contact.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;
}

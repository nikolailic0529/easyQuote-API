<?php

namespace App\Contracts\Repositories\QuoteTemplate;

use App\Models\QuoteTemplate\ContractTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Closure;

interface ContractTemplateRepositoryInterface
{
    /**
     * Make Query builder instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(): Builder;

    /**
     * Paginate existing Contract Templates.
     *
     * @return mixed
     */
    public function paginate();

    /**
     * Search over existing Contract Templates.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Get Contract Templates by specified Country.
     *
     * @param string $countryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function country(string $countryId): EloquentCollection;

    /**
     * Find Contract Template by given id.
     *
     * @param string $id
     * @return \App\Models\QuoteTemplate\ContractTemplate
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): ContractTemplate;

    /**
     * Retrieve random existing Company.
     *
     * @param int $limit
     * @param Closure $scope
     * @return \App\Models\QuoteTemplate\ContractTemplate|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1, ?Closure $scope = null);

    /**
     * Get Contract Templates by specified Company, Vendor, Country.
     *
     * @param mixed $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByCompanyVendorCountry($request): EloquentCollection;

    /**
     * Template Tags and other Data for Template Designer
     *
     * @param string $id
     * @return \Illuminate\Support\Collection
     */
    public function designer(string $id): Collection;

    /**
     * Create a new Contract Template with provided attributes.
     *
     * @param array $attributes
     * @return \App\Models\QuoteTemplate\ContractTemplate
     */
    public function create(array $attributes): ContractTemplate;

    /**
     * Update the specified Contract Template with provided attributes.
     *
     * @param array $attributes
     * @param string $id
     * @return \App\Models\QuoteTemplate\ContractTemplate
     */
    public function update(array $attributes, string $id): ContractTemplate;

    /**
     * Delete the specified Contract Template.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Activate specified Contract Template.
     *
     * @param string $id
     * @return bool
     */
    public function activate(string $id): bool;

    /**
     * Deactivate specified Contract Template.
     *
     * @param string $id
     * @return bool
     */
    public function deactivate(string $id): bool;

    /**
     * Copy the specified Contract Template.
     *
     * @param string $id
     * @return mixed
     */
    public function copy(string $id);
}

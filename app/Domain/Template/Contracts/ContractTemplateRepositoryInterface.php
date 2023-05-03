<?php

namespace App\Domain\Template\Contracts;

use App\Domain\Rescue\Models\ContractTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

interface ContractTemplateRepositoryInterface
{
    /**
     * Make Query builder instance.
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
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Get Contract Templates by specified Country.
     */
    public function country(string $countryId): EloquentCollection;

    /**
     * Find Contract Template by given id.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function find(string $id): ContractTemplate;

    /**
     * Retrieve random existing Company.
     *
     * @param \Closure $scope
     *
     * @return \App\Domain\Rescue\Models\ContractTemplate|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1, ?\Closure $scope = null);

    /**
     * Get Contract Templates by specified Company, Vendor, Country.
     *
     * @param mixed $request
     */
    public function findByCompanyVendorCountry($request): EloquentCollection;

    /**
     * Template Tags and other Data for Template Designer.
     */
    public function designer(string $id): Collection;

    /**
     * Create a new Contract Template with provided attributes.
     */
    public function create(array $attributes): ContractTemplate;

    /**
     * Update the specified Contract Template with provided attributes.
     */
    public function update(array $attributes, string $id): ContractTemplate;

    /**
     * Delete the specified Contract Template.
     */
    public function delete(string $id): bool;

    /**
     * Activate specified Contract Template.
     */
    public function activate(string $id): bool;

    /**
     * Deactivate specified Contract Template.
     */
    public function deactivate(string $id): bool;

    /**
     * Copy the specified Contract Template.
     *
     * @return mixed
     */
    public function copy(string $id);
}

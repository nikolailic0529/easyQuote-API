<?php

namespace App\Contracts\Repositories\QuoteTemplate;

use App\Http\Requests\GetQuoteTemplatesRequest;
use App\Http\Requests\QuoteTemplate\{
    StoreQuoteTemplateRequest,
    UpdateQuoteTemplateRequest
};
use App\Models\Template\QuoteTemplate;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use \Closure;

interface QuoteTemplateRepositoryInterface
{
    /**
     * Get all Quote Templates.
     *
     * @return mixed
     */
    public function all();

    /**
     * Retrieve all user defined Quote Templates.
     *
     * @param array $columns
     * @param boolean $cursor
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\LazyCollection
     */
    public function allUserDefined(array $columns = ['*'], bool $cursor = false);

    /**
     * Search over Quote Templates.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Retrieve a listing of the existing Quote Templates by specified Country.
     *
     * @param string $countryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function country(string $countryId): EloquentCollection;

    /**
     * Get Quote Template by id.
     *
     * @param string $id
     * @return \App\Models\Template\QuoteTemplate
     */
    public function find(string $id): QuoteTemplate;

    /**
     * Retrieve random existing Company.
     *
     * @param int $limit
     * @param Closure $scope
     * @return \App\Models\Template\QuoteTemplate|\Illuminate\Database\Eloquent\Collection|null
     */
    public function random(int $limit = 1, ?Closure $scope = null);

    /**
     * Get Quote Templates by Company, Vendor, Country.
     *
     * @param mixed $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByCompanyVendorCountry($request): EloquentCollection;

    /**
     * Template Tags and other Data for Template Designer
     *
     * @param string $id
     * @return Collection
     */
    public function designer(string $id): Collection;

    /**
     * Create Quote Template.
     *
     * @param \App\Http\Requests\QuoteTemplate\StoreQuoteTemplateRequest|array $request
     * @return \App\Models\Template\QuoteTemplate
     */
    public function create($request): QuoteTemplate;

    /**
     * Update specified Quote Template.
     *
     * @param \App\Http\Requests\QuoteTemplate\UpdateQuoteTemplateRequest $request
     * @param string $id
     * @return \App\Models\Template\QuoteTemplate
     */
    public function update(UpdateQuoteTemplateRequest $request, string $id): QuoteTemplate;

    /**
     * Delete specified Quote Template.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Activate specified Quote Template.
     *
     * @param string $id
     * @return bool
     */
    public function activate(string $id): bool;

    /**
     * Deactivate specified Quote Template.
     *
     * @param string $id
     * @return bool
     */
    public function deactivate(string $id): bool;

    /**
     * Copy a specified Quote Template.
     * Return a newly copied Template id. ["id" => copied_template_id]
     *
     * @param string $id
     * @return array
     */
    public function copy(string $id): array;
}

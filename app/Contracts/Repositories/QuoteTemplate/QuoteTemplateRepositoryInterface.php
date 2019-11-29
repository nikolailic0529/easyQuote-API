<?php

namespace App\Contracts\Repositories\QuoteTemplate;

use App\Builder\Pagination\Paginator;
use App\Http\Requests\QuoteTemplate\{
    StoreQuoteTemplateRequest,
    UpdateQuoteTemplateRequest
};
use App\Models\QuoteTemplate\QuoteTemplate;
use Illuminate\Support\Collection;

interface QuoteTemplateRepositoryInterface
{
    /**
     * Get all Quote Templates.
     *
     * @return mixed
     */
    public function all();

    /**
     * Search over Quote Templates.
     *
     * @param string $query
     * @return mixed
     */
    public function search(string $query = '');

    /**
     * Get Quote Template by id.
     *
     * @param string $id
     * @return QuoteTemplate
     */
    public function find(string $id): QuoteTemplate;

    /**
     * Get Quote Templates by Company, Vendor, Country.
     *
     * @param string $companyId
     * @param string $vendorId
     * @param string $countryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByCompanyVendorCountry(string $companyId, string $vendorId, string $countryId);

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
     * @param StoreQuoteTemplateRequest $request
     * @return QuoteTemplate
     */
    public function create(StoreQuoteTemplateRequest $request): QuoteTemplate;

    /**
     * Update specified Quote Template.
     *
     * @param UpdateQuoteTemplateRequest $request
     * @param string $id
     * @return QuoteTemplate
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
     * Copy specified Quote Template.
     *
     * @param string $id
     * @return bool
     */
    public function copy(string $id): bool;
}

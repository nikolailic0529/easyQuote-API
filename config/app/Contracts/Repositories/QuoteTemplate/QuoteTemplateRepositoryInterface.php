<?php namespace App\Contracts\Repositories\QuoteTemplate;

use App\Http\Requests\GetQuoteTemplatesRequest;

interface QuoteTemplateRepositoryInterface
{
    /**
     * Get Quote Template by id
     *
     * @param string $id
     * @return \App\Models\QuoteTemplate\QuoteTemplate
     */
    public function find(string $id);

    /**
     * Get Quote Templates by Company, Vendor, Country
     *
     * @param string $companyId
     * @param string $vendorId
     * @param string $countryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByCompanyVendorCountry(string $companyId, string $vendorId, string $countryId);
}
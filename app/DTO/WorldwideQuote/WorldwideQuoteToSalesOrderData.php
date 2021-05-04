<?php

namespace App\DTO\WorldwideQuote;

use Spatie\DataTransferObject\DataTransferObject;

final class WorldwideQuoteToSalesOrderData extends DataTransferObject
{
    public string $worldwide_quote_id;

    public string $worldwide_quote_number;

    public string $vat_number;

    public string $vat_type;

    public SalesOrderCompanyData $company;

    /**
     * @var \App\DTO\WorldwideQuote\SalesOrderVendorData[]
     */
    public array $vendors;

    /**
     * @var \App\DTO\WorldwideQuote\SalesOrderCountryData[]
     */
    public array $countries;

    /**
     * @var \App\DTO\WorldwideQuote\SalesOrderTemplateData[]
     */
    public array $sales_order_templates;
}

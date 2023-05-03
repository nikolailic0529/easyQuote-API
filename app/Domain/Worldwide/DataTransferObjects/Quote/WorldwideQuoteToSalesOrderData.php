<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class WorldwideQuoteToSalesOrderData extends DataTransferObject
{
    public string $worldwide_quote_id;

    public string $worldwide_quote_number;

    public string $vat_number;

    public string $vat_type;

    public SalesOrderCompanyData $company;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\SalesOrderVendorData[]
     */
    public array $vendors;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\SalesOrderCountryData[]
     */
    public array $countries;

    /**
     * @var \App\Domain\Worldwide\DataTransferObjects\Quote\SalesOrderTemplateData[]
     */
    public array $sales_order_templates;

    public string $contract_type;
}

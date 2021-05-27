<?php

use App\Models\Address;
use App\Models\Asset;
use App\Models\Collaboration\Invitation;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer\WorldwideCustomer;
use App\Models\Data\Country;
use App\Models\HpeContract;
use App\Models\Opportunity;
use App\Models\OpportunityForm\OpportunityForm;
use App\Models\Pipeline\Pipeline;
use App\Models\Quote\Contract;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\Margin\CountryMargin;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\System\Activity;
use App\Models\Team;
use App\Models\Template\ContractTemplate;
use App\Models\Template\HpeContractTemplate;
use App\Models\Template\QuoteTemplate;
use App\Models\User;
use App\Models\Vendor;

return [
    'reindex_models' => [
        User::class,
        Role::class,
        Quote::class,
        Contract::class,
        HpeContract::class,
        WorldwideQuote::class,
        WorldwideCustomer::class,
        QuoteTemplate::class,
        ContractTemplate::class,
        HpeContractTemplate::class,
        CountryMargin::class,
        MultiYearDiscount::class,
        PrePayDiscount::class,
        PromotionalDiscount::class,
        SND::class,
        Company::class,
        Vendor::class,
        Invitation::class,
        Activity::class,
        Address::class,
        Contact::class,
        Country::class,
        Asset::class,
        ImportableColumn::class,
        Opportunity::class,
        SalesOrder::class,
        Team::class,
        Pipeline::class,
        OpportunityForm::class,
    ],
];

<?php

use App\Models\Role;
use App\Models\User;
use App\Models\Asset;
use App\Models\Vendor;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\HpeContract;
use App\Models\Quote\Quote;
use App\Models\Data\Country;
use App\Models\Quote\Contract;
use App\Models\System\Activity;
use App\Models\Quote\Discount\SND;
use App\Models\Template\QuoteTemplate;
use App\Models\Template\TemplateField;
use App\Models\Collaboration\Invitation;
use App\Models\Template\ContractTemplate;
use App\Models\Quote\Margin\CountryMargin;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\Template\HpeContractTemplate;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;

return [
    'reindex_models' => [
        User::class,
        Role::class,
        Quote::class,
        Contract::class,
        HpeContract::class,
        QuoteTemplate::class,
        ContractTemplate::class,
        HpeContractTemplate::class,
        TemplateField::class,
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
    ],
];
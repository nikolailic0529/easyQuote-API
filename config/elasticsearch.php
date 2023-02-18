<?php

use App\Domain\Activity\Models\Activity;
use App\Domain\Address\Models\Address;
use App\Domain\Asset\Models\Asset;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Country\Models\Country;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Invitation\Models\Invitation;
use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Rescue\Models\Contract;
use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Team\Models\Team;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\OpportunityForm;
use App\Domain\Worldwide\Models\SalesOrder;
use App\Domain\Worldwide\Models\WorldwideQuote;

return [
    'reindex_models' => [
        User::class,
        Role::class,
        Quote::class,
        Contract::class,
        HpeContract::class,
        WorldwideQuote::class,
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
        Opportunity::class,
        SalesOrder::class,
        Team::class,
        Pipeline::class,
        OpportunityForm::class,
    ],
];

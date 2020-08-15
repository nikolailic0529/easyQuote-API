<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\{
    Address,
    Asset,
    User,
    Role,
    Company,
    Vendor,
    Contact,
    Task,
    Customer\Customer,
    Quote\Quote,
    Quote\QuoteNote,
    Quote\Contract,
    Quote\Margin\Margin,
    Quote\Discount\Discount,
    QuoteFile\QuoteFile,
    QuoteFile\ImportableColumn,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\ContractTemplate,
    Collaboration\Invitation,
    System\SystemSetting,
    System\Activity,
    System\Notification,
    Data\Country,
    HpeContract,
};
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\QuoteTemplate\HpeContractTemplate;
use App\Policies\{
    ActivityPolicy,
    AddressPolicy,
    AssetPolicy,
    CompanyPolicy,
    ContactPolicy,
    ContractPolicy,
    ContractTemplatePolicy,
    CountryPolicy,
    CustomerPolicy,
    DiscountPolicy,
    HpeContractPolicy,
    HpeContractTemplatePolicy,
    ImportableColumnPolicy,
    InvitationPolicy,
    MarginPolicy,
    MultiYearDiscountPolicy,
    NotificationPolicy,
    PrePayDiscountPolicy,
    PromotionalDiscountPolicy,
    QuoteFilePolicy,
    QuoteNotePolicy,
    QuotePolicy,
    QuoteTaskTemplatePolicy,
    QuoteTemplatePolicy,
    RolePolicy,
    SNDPolicy,
    SystemSettingPolicy,
    TaskPolicy,
    UserPolicy,
    VendorPolicy,
};
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\{
    Passport,
    Client,
    PersonalAccessClient,
};
use Webpatser\Uuid\Uuid;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Quote::class                => QuotePolicy::class,
        Contract::class             => ContractPolicy::class,
        QuoteFile::class            => QuoteFilePolicy::class,
        QuoteNote::class            => QuoteNotePolicy::class,
        Task::class                 => TaskPolicy::class,

        QuoteTemplate::class        => QuoteTemplatePolicy::class,
        ContractTemplate::class     => ContractTemplatePolicy::class,
        HpeContractTemplate::class  => HpeContractTemplatePolicy::class,

        Company::class              => CompanyPolicy::class,
        Vendor::class               => VendorPolicy::class,
        
        SND::class                  => SNDPolicy::class,
        PrePayDiscount::class       => PrePayDiscountPolicy::class,
        MultiYearDiscount::class    => MultiYearDiscountPolicy::class,
        PromotionalDiscount::class  => PromotionalDiscountPolicy::class,

        Margin::class               => MarginPolicy::class,
        Role::class                 => RolePolicy::class,
        User::class                 => UserPolicy::class,
        Invitation::class           => InvitationPolicy::class,
        SystemSetting::class        => SystemSettingPolicy::class,
        Activity::class             => ActivityPolicy::class,
        Address::class              => AddressPolicy::class,
        Contact::class              => ContactPolicy::class,
        Notification::class         => NotificationPolicy::class,
        Customer::class             => CustomerPolicy::class,
        Country::class              => CountryPolicy::class,
        ImportableColumn::class     => ImportableColumnPolicy::class,
        Asset::class                => AssetPolicy::class,
        HpeContract::class          => HpeContractPolicy::class,
    ];

    public function register()
    {
        Passport::ignoreMigrations();
    }

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $this->registerGates();

        Client::creating(function (Client $client) {
            $client->setIncrementing(false);
            $client->{$client->getKeyName()} = Uuid::generate(4)->string;
        });

        Client::retrieved(function (Client $client) {
            $client->setIncrementing(false);
        });

        PersonalAccessClient::creating(function (PersonalAccessClient $client) {
            $client->setIncrementing(false);
            $client->{$client->getKeyName()} = Uuid::generate(4)->string;
        });

        Passport::routes();
        Passport::personalAccessTokensExpireIn(now()->addMinutes(config('auth.tokens.expire')));
    }

    protected function registerGates()
    {
        Gate::define('view_quote_task_template', QuoteTaskTemplatePolicy::class.'@view');

        Gate::define('update_quote_task_template', QuoteTaskTemplatePolicy::class.'@update');
    }
}

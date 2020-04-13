<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\{
    Address,
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
};
use App\Policies\{
    ActivityPolicy,
    AddressPolicy,
    CompanyPolicy,
    ContactPolicy,
    ContractPolicy,
    ContractTemplatePolicy,
    CountryPolicy,
    CustomerPolicy,
    DiscountPolicy,
    ImportableColumnPolicy,
    InvitationPolicy,
    MarginPolicy,
    NotificationPolicy,
    QuoteFilePolicy,
    QuoteNotePolicy,
    QuotePolicy,
    QuoteTemplatePolicy,
    RolePolicy,
    SystemSettingPolicy,
    TaskPolicy,
    UserPolicy,
    VendorPolicy,
};
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
        Company::class              => CompanyPolicy::class,
        Vendor::class               => VendorPolicy::class,
        Discount::class             => DiscountPolicy::class,
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
        ImportableColumn::class     => ImportableColumnPolicy::class
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

        Client::creating(function (Client $client) {
            $client->incrementing = false;
            $client->id = Uuid::generate(4)->string;
        });

        Client::retrieved(function (Client $client) {
            $client->incrementing = false;
        });

        PersonalAccessClient::creating(function (PersonalAccessClient $client) {
            $client->incrementing = false;
            $client->id = Uuid::generate(4)->string;
        });

        Passport::routes();
        Passport::personalAccessTokensExpireIn(now()->addMinutes(config('auth.tokens.expire')));
    }
}

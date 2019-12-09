<?php

namespace App\Providers;

use App\Models\{
    Address,
    User,
    Role,
    Company,
    Vendor,
    Contact,
    Quote\Quote,
    Quote\Margin\Margin,
    Quote\Discount\Discount,
    QuoteFile\QuoteFile,
    QuoteTemplate\QuoteTemplate,
    Collaboration\Invitation,
    System\SystemSetting,
    System\Activity
};
use App\Policies\{
    ActivityPolicy,
    AddressPolicy,
    CompanyPolicy,
    ContactPolicy,
    DiscountPolicy,
    InvitationPolicy,
    MarginPolicy,
    QuoteFilePolicy,
    QuotePolicy,
    QuoteTemplatePolicy,
    RolePolicy,
    SystemSettingPolicy,
    UserPolicy,
    VendorPolicy
};
use Laravel\Passport\{
    Passport,
    Client,
    PersonalAccessClient
};
use Webpatser\Uuid\Uuid;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Quote::class => QuotePolicy::class,
        QuoteFile::class => QuoteFilePolicy::class,
        QuoteTemplate::class => QuoteTemplatePolicy::class,
        Company::class => CompanyPolicy::class,
        Vendor::class => VendorPolicy::class,
        Discount::class => DiscountPolicy::class,
        Margin::class => MarginPolicy::class,
        Role::class => RolePolicy::class,
        User::class => UserPolicy::class,
        Invitation::class => InvitationPolicy::class,
        SystemSetting::class => SystemSettingPolicy::class,
        Activity::class => ActivityPolicy::class,
        Address::class => AddressPolicy::class,
        Contact::class => ContactPolicy::class
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

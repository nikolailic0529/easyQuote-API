<?php

namespace App\Providers;

use App\Models\{
    User,
    Role,
    Company,
    Vendor,
    Quote\Quote,
    Quote\Margin\Margin,
    Quote\Discount\Discount,
    QuoteFile\QuoteFile,
    QuoteTemplate\QuoteTemplate,
    Collaboration\Invitation,
    System\SystemSetting
};
use App\Policies\{
    CompanyPolicy,
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
use Laravel\Passport\Passport;
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
        SystemSetting::class => SystemSettingPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
    }
}

<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\Models\Role;
use App\Models\Task;
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
use App\Models\Quote\QuoteNote;
use App\Models\System\Activity;
use App\Models\Customer\Customer;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\Margin\Margin;
use App\Models\QuoteFile\QuoteFile;
use App\Models\System\Notification;
use App\Models\System\SystemSetting;
use App\Models\Template\QuoteTemplate;
use App\Models\Collaboration\Invitation;
use App\Models\Template\ContractTemplate;
use App\Models\QuoteFile\ImportableColumn;
use App\Models\Template\HpeContractTemplate;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;

use App\Policies\SNDPolicy;
use App\Policies\RolePolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Policies\AssetPolicy;
use App\Policies\QuotePolicy;
use App\Policies\MarginPolicy;
use App\Policies\VendorPolicy;
use App\Policies\AddressPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ContactPolicy;
use App\Policies\CountryPolicy;
use App\Policies\ActivityPolicy;
use App\Policies\ContractPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\QuoteFilePolicy;
use App\Policies\QuoteNotePolicy;
use App\Policies\InvitationPolicy;
use App\Policies\HpeContractPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\QuoteTemplatePolicy;
use App\Policies\SystemSettingPolicy;
use App\Policies\PrePayDiscountPolicy;
use App\Policies\ContractTemplatePolicy;
use App\Policies\ImportableColumnPolicy;
use App\Policies\MultiYearDiscountPolicy;
use App\Policies\QuoteTaskTemplatePolicy;
use App\Policies\HpeContractTemplatePolicy;
use App\Policies\PromotionalDiscountPolicy;
use Laravel\Passport\{Passport, Client, PersonalAccessClient};
use Illuminate\Support\Facades\Gate;
use Webpatser\Uuid\Uuid;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Quote::class                    => QuotePolicy::class,
        Contract::class                 => ContractPolicy::class,
        QuoteFile::class                => QuoteFilePolicy::class,
        QuoteNote::class                => QuoteNotePolicy::class,
        Task::class                     => TaskPolicy::class,

        QuoteTemplate::class            => QuoteTemplatePolicy::class,
        ContractTemplate::class         => ContractTemplatePolicy::class,
        HpeContractTemplate::class      => HpeContractTemplatePolicy::class,

        Company::class                  => CompanyPolicy::class,
        Vendor::class                   => VendorPolicy::class,

        SND::class                      => SNDPolicy::class,
        PrePayDiscount::class           => PrePayDiscountPolicy::class,
        MultiYearDiscount::class        => MultiYearDiscountPolicy::class,
        PromotionalDiscount::class      => PromotionalDiscountPolicy::class,

        Margin::class                   => MarginPolicy::class,
        Role::class                     => RolePolicy::class,
        User::class                     => UserPolicy::class,
        Invitation::class               => InvitationPolicy::class,
        SystemSetting::class            => SystemSettingPolicy::class,
        Activity::class                 => ActivityPolicy::class,
        Address::class                  => AddressPolicy::class,
        Contact::class                  => ContactPolicy::class,
        Notification::class             => NotificationPolicy::class,
        Customer::class                 => CustomerPolicy::class,
        Country::class                  => CountryPolicy::class,
        ImportableColumn::class         => ImportableColumnPolicy::class,
        Asset::class                    => AssetPolicy::class,
        HpeContract::class              => HpeContractPolicy::class,
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
        Gate::define('view_quote_task_template', QuoteTaskTemplatePolicy::class . '@view');

        Gate::define('update_quote_task_template', QuoteTaskTemplatePolicy::class . '@update');
    }
}

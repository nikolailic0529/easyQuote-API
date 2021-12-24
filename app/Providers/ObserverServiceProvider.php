<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\{
    Vendor,
    Quote\Quote,
    Quote\Margin\CountryMargin,
    Template\QuoteTemplate,
    Collaboration\Invitation,
    System\SystemSetting,
    Customer\Customer,
    Template\ContractTemplate,
    System\Notification,
};
use App\Models\Quote\Contract;
use App\Models\Template\HpeContractTemplate;
use App\Observers\{
    VendorObserver,
    QuoteObserver,
    MarginObserver,
    QuoteTemplateObserver,
    Collaboration\InvitationObserver,
    ContractObserver,
    CustomerObserver,
    NotificationObserver,
    SystemSettingObserver,
};

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Quote::observe(QuoteObserver::class);

        Contract::observe(ContractObserver::class);

        CountryMargin::observe(MarginObserver::class);

        Vendor::observe(VendorObserver::class);

        QuoteTemplate::observe(QuoteTemplateObserver::class);

        ContractTemplate::observe(QuoteTemplateObserver::class);
        
        HpeContractTemplate::observe(QuoteTemplateObserver::class);

        Invitation::observe(InvitationObserver::class);

        SystemSetting::observe(SystemSettingObserver::class);

        Customer::observe(CustomerObserver::class);

        Notification::observe(NotificationObserver::class);
    }
}

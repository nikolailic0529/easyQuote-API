<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\{
    Vendor,
    Quote\Quote,
    Quote\Margin\CountryMargin,
    Quote\Discount\MultiYearDiscount,
    Quote\Discount\PrePayDiscount,
    Quote\Discount\PromotionalDiscount,
    Quote\Discount\SND,
    QuoteTemplate\QuoteTemplate,
    Collaboration\Invitation,
    System\SystemSetting,
    Customer\Customer,
    QuoteTemplate\ContractTemplate,
    System\Notification
};
use App\Models\Quote\Contract;
use App\Observers\{
    VendorObserver,
    QuoteObserver,
    MarginObserver,
    Discount\MultiYearDiscountObserver,
    Discount\PrePayDiscountObserver,
    Discount\PromotionalDiscountObserver,
    Discount\SNDobserver,
    QuoteTemplateObserver,
    Collaboration\InvitationObserver,
    ContractObserver,
    CustomerObserver,
    NotificationObserver,
    SystemSettingObserver
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

        MultiYearDiscount::observe(MultiYearDiscountObserver::class);

        PrePayDiscount::observe(PrePayDiscountObserver::class);

        PromotionalDiscount::observe(PromotionalDiscountObserver::class);

        SND::observe(SNDobserver::class);

        Vendor::observe(VendorObserver::class);

        QuoteTemplate::observe(QuoteTemplateObserver::class);

        ContractTemplate::observe(QuoteTemplateObserver::class);

        Invitation::observe(InvitationObserver::class);

        SystemSetting::observe(SystemSettingObserver::class);

        Customer::observe(CustomerObserver::class);

        Notification::observe(NotificationObserver::class);
    }
}

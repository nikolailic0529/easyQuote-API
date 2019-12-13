<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\{
    Company,
    Vendor,
    Quote\Quote,
    Quote\Margin\CountryMargin,
    Quote\Discount\MultiYearDiscount,
    Quote\Discount\PrePayDiscount,
    Quote\Discount\PromotionalDiscount,
    Quote\Discount\SND,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField,
    Collaboration\Invitation,
    System\SystemSetting
};
use App\Models\Customer\Customer;
use App\Observers\{
    CompanyObserver,
    VendorObserver,
    QuoteObserver,
    MarginObserver,
    Discount\MultiYearDiscountObserver,
    Discount\PrePayDiscountObserver,
    Discount\PromotionalDiscountObserver,
    Discount\SNDobserver,
    QuoteTemplateObserver,
    TemplateFieldObserver,
    Collaboration\InvitationObserver,
    CustomerObserver,
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

        CountryMargin::observe(MarginObserver::class);

        MultiYearDiscount::observe(MultiYearDiscountObserver::class);

        PrePayDiscount::observe(PrePayDiscountObserver::class);

        PromotionalDiscount::observe(PromotionalDiscountObserver::class);

        SND::observe(SNDobserver::class);

        Vendor::observe(VendorObserver::class);

        Company::observe(CompanyObserver::class);

        QuoteTemplate::observe(QuoteTemplateObserver::class);

        TemplateField::observe(TemplateFieldObserver::class);

        Invitation::observe(InvitationObserver::class);

        SystemSetting::observe(SystemSettingObserver::class);

        Customer::observe(CustomerObserver::class);
    }
}

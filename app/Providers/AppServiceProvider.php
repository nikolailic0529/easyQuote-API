<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\{
    Passport,
    Client,
    PersonalAccessClient
};
use Webpatser\Uuid\Uuid;
use App\Http\Controllers\API\AuthController;
use App\Contracts\{
    Services\AuthServiceInterface,
    Services\ParserServiceInterface,
    Services\WordParserInterface,
    Services\PdfParserInterface,
    Services\QuoteServiceInterface,
    Repositories\TimezoneRepositoryInterface,
    Repositories\CountryRepositoryInterface,
    Repositories\UserRepositoryInterface,
    Repositories\AccessAttemptRepositoryInterface,
    Repositories\LanguageRepositoryInterface,
    Repositories\CurrencyRepositoryInterface,
    Repositories\QuoteFile\QuoteFileRepositoryInterface,
    Repositories\QuoteFile\FileFormatRepositoryInterface,
    Repositories\QuoteFile\ImportableColumnRepositoryInterface,
    Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface,
    Repositories\Quote\QuoteRepositoryInterface,
    Repositories\Quote\Margin\MarginRepositoryInterface,
    Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface,
    Repositories\QuoteTemplate\TemplateFieldRepositoryInterface,
    Repositories\Customer\CustomerRepositoryInterface,
    Repositories\System\SystemSettingRepositoryInterface,
    Repositories\System\ActivityRepositoryInterface,
    Repositories\Quote\Discount\MultiYearDiscountRepositoryInterface,
    Repositories\Quote\Discount\PromotionalDiscountRepositoryInterface,
    Repositories\Quote\Discount\PrePayDiscountRepositoryInterface,
    Repositories\Quote\Discount\SNDrepositoryInterface,
    Repositories\VendorRepositoryInterface,
    Repositories\CompanyRepositoryInterface,
    Repositories\RoleRepositoryInterface,
    Repositories\InvitationRepositoryInterface,
    Repositories\Quote\QuoteDraftedRepositoryInterface,
    Repositories\Quote\QuoteSubmittedRepositoryInterface,
    Repositories\AddressRepositoryInterface,
    Repositories\ContactRepositoryInterface
};
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
    SystemSettingObserver
};
use App\Repositories\{
    TimezoneRepository,
    CountryRepository,
    UserRepository,
    AccessAttemptRepository,
    AddressRepository,
    LanguageRepository,
    CurrencyRepository,
    QuoteFile\QuoteFileRepository,
    QuoteFile\FileFormatRepository,
    QuoteFile\ImportableColumnRepository,
    QuoteFile\DataSelectSeparatorRepository,
    Quote\QuoteRepository,
    Quote\Margin\MarginRepository,
    QuoteTemplate\QuoteTemplateRepository,
    QuoteTemplate\TemplateFieldRepository,
    Customer\CustomerRepository,
    System\SystemSettingRepository,
    System\ActivityRepository,
    Quote\Discount\MultiYearDiscountRepository,
    Quote\Discount\PromotionalDiscountRepository,
    Quote\Discount\PrePayDiscountRepository,
    Quote\Discount\SNDrepository,
    Quote\QuoteDraftedRepository,
    Quote\QuoteSubmittedRepository,
    VendorRepository,
    CompanyRepository,
    ContactRepository,
    InvitationRepository,
    RoleRepository
};
use App\Services\{
    AuthService,
    ParserService,
    WordParser,
    QuoteService,
    PdfParser\PdfParser
};
use Elasticsearch\{
    Client as ElasticsearchClient,
    ClientBuilder as ElasticsearchBuilder
};
use Schema;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        TimezoneRepositoryInterface::class => TimezoneRepository::class,
        CountryRepositoryInterface::class => CountryRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
        AccessAttemptRepositoryInterface::class => AccessAttemptRepository::class,
        LanguageRepositoryInterface::class => LanguageRepository::class,
        CurrencyRepositoryInterface::class => CurrencyRepository::class,
        QuoteFileRepositoryInterface::class => QuoteFileRepository::class,
        FileFormatRepositoryInterface::class => FileFormatRepository::class,
        ImportableColumnRepositoryInterface::class => ImportableColumnRepository::class,
        QuoteRepositoryInterface::class => QuoteRepository::class,
        QuoteTemplateRepositoryInterface::class => QuoteTemplateRepository::class,
        TemplateFieldRepositoryInterface::class => TemplateFieldRepository::class,
        DataSelectSeparatorRepositoryInterface::class => DataSelectSeparatorRepository::class,
        CustomerRepositoryInterface::class => CustomerRepository::class,
        SystemSettingRepositoryInterface::class => SystemSettingRepository::class,
        MarginRepositoryInterface::class => MarginRepository::class,
        QuoteServiceInterface::class => QuoteService::class,
        MultiYearDiscountRepositoryInterface::class => MultiYearDiscountRepository::class,
        PrePayDiscountRepositoryInterface::class => PrePayDiscountRepository::class,
        PromotionalDiscountRepositoryInterface::class => PromotionalDiscountRepository::class,
        SNDrepositoryInterface::class => SNDrepository::class,
        VendorRepositoryInterface::class => VendorRepository::class,
        CompanyRepositoryInterface::class => CompanyRepository::class,
        ParserServiceInterface::class => ParserService::class,
        WordParserInterface::class => WordParser::class,
        PdfParserInterface::class => PdfParser::class,
        RoleRepositoryInterface::class => RoleRepository::class,
        QuoteDraftedRepositoryInterface::class => QuoteDraftedRepository::class,
        QuoteSubmittedRepositoryInterface::class => QuoteSubmittedRepository::class,
        InvitationRepositoryInterface::class => InvitationRepository::class,
        ActivityRepositoryInterface::class => ActivityRepository::class,
        AddressRepositoryInterface::class => AddressRepository::class,
        ContactRepositoryInterface::class => ContactRepository::class
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Passport::ignoreMigrations();

        $this->app->when(AuthController::class)->needs(AuthServiceInterface::class)->give(AuthService::class);

        $this->app->bind(ElasticsearchClient::class, function () {
            return ElasticsearchBuilder::create()->setHosts(app('config')->get('services.search.hosts'))->build();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        $this->passportSettings();

        $this->registerObservers();
    }

    protected function registerObservers()
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
    }

    protected function passportSettings()
    {
        Client::creating(function (Client $client) {
            $client->incrementing = false;
            $client->id = Uuid::generate()->string;
        });

        Client::retrieved(function (Client $client) {
            $client->incrementing = false;
        });

        PersonalAccessClient::creating(function (PersonalAccessClient $client) {
            $client->incrementing = false;
            $client->id = Uuid::generate()->string;
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\{
    Services\AuthServiceInterface,
    Services\ParserServiceInterface,
    Services\WordParserInterface,
    Services\PdfParserInterface,
    Services\CsvParserInterface,
    Services\QuoteServiceInterface,
    Services\ReportLoggerInterface,
    Services\SlackInterface,
    Services\NotificationInterface,
    Services\UIServiceInterface,
    Services\HttpInterface,
    Services\ExchangeRateServiceInterface,
    Services\MaintenanceServiceInterface,
    Services\PermissionBroker as PermissionBrokerContract,

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
    Repositories\QuoteTemplate\ContractTemplateRepositoryInterface,
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
    Repositories\ContactRepositoryInterface,
    Factories\FailureInterface,
    Repositories\System\NotificationRepositoryInterface,
    Repositories\System\ClientCredentialsInterface,
    Repositories\System\BuildRepositoryInterface,
    Repositories\ExchangeRateRepositoryInterface,
    Repositories\Contract\ContractStateRepositoryInterface,
    Repositories\Contract\ContractDraftedRepositoryInterface,
    Repositories\Contract\ContractSubmittedRepositoryInterface,
    Repositories\Quote\QuoteNoteRepositoryInterface,
    Repositories\TaskRepositoryInterface,
    Repositories\UserForm as UserFormContract,
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
    Quote\QuoteStateRepository,
    Quote\Margin\MarginRepository,
    QuoteTemplate\QuoteTemplateRepository,
    QuoteTemplate\ContractTemplateRepository,
    QuoteTemplate\TemplateFieldRepository,
    Customer\CustomerRepository,
    System\SystemSettingRepository,
    System\ActivityRepository,
    System\NotificationRepository,
    System\ClientCredentialsRepository,
    System\BuildRepository,
    Quote\Discount\MultiYearDiscountRepository,
    Quote\Discount\PromotionalDiscountRepository,
    Quote\Discount\PrePayDiscountRepository,
    Quote\Discount\SNDrepository,
    Quote\QuoteDraftedRepository,
    Quote\QuoteSubmittedRepository,
    Quote\ContractStateRepository,
    Quote\ContractDraftedRepository,
    Quote\ContractSubmittedRepository,
    Quote\QuoteNoteRepository,
    VendorRepository,
    CompanyRepository,
    ContactRepository,
    ExchangeRateRepository,
    InvitationRepository,
    RoleRepository,
    TaskRepository,
    TaskTemplate\QuoteTaskTemplateStore,
    TaskTemplate\TaskTemplateManager,
    UserForm,
};
use App\Services\{
    ActivityLogger,
    Auth\AuthService,
    Auth\AuthenticatedCase,
    CsvParser,
    NotificationStorage,
    ParserService,
    WordParser,
    QuoteService,
    PdfParser\PdfParser,
    ReportLogger,
    HttpService,
    MaintenanceService,
    PermissionBroker,
    SlackClient,
    UIService
};
use Elasticsearch\{
    Client as ElasticsearchClient,
    ClientBuilder as ElasticsearchBuilder
};
use App\Factories\Failure\Failure;
use App\Http\Resources\RequestQueryFilter;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    public $singletons = [
        TimezoneRepositoryInterface::class              => TimezoneRepository::class,
        CountryRepositoryInterface::class               => CountryRepository::class,
        UserRepositoryInterface::class                  => UserRepository::class,
        AccessAttemptRepositoryInterface::class         => AccessAttemptRepository::class,
        LanguageRepositoryInterface::class              => LanguageRepository::class,
        CurrencyRepositoryInterface::class              => CurrencyRepository::class,
        QuoteFileRepositoryInterface::class             => QuoteFileRepository::class,
        FileFormatRepositoryInterface::class            => FileFormatRepository::class,
        ImportableColumnRepositoryInterface::class      => ImportableColumnRepository::class,
        QuoteRepositoryInterface::class                 => QuoteStateRepository::class,
        QuoteTemplateRepositoryInterface::class         => QuoteTemplateRepository::class,
        ContractTemplateRepositoryInterface::class      => ContractTemplateRepository::class,
        TemplateFieldRepositoryInterface::class         => TemplateFieldRepository::class,
        DataSelectSeparatorRepositoryInterface::class   => DataSelectSeparatorRepository::class,
        CustomerRepositoryInterface::class              => CustomerRepository::class,
        SystemSettingRepositoryInterface::class         => SystemSettingRepository::class,
        FailureInterface::class                         => Failure::class,
        MarginRepositoryInterface::class                => MarginRepository::class,
        QuoteServiceInterface::class                    => QuoteService::class,
        MultiYearDiscountRepositoryInterface::class     => MultiYearDiscountRepository::class,
        PrePayDiscountRepositoryInterface::class        => PrePayDiscountRepository::class,
        PromotionalDiscountRepositoryInterface::class   => PromotionalDiscountRepository::class,
        SNDrepositoryInterface::class                   => SNDrepository::class,
        VendorRepositoryInterface::class                => VendorRepository::class,
        CompanyRepositoryInterface::class               => CompanyRepository::class,
        ParserServiceInterface::class                   => ParserService::class,
        WordParserInterface::class                      => WordParser::class,
        PdfParserInterface::class                       => PdfParser::class,
        CsvParserInterface::class                       => CsvParser::class,
        RoleRepositoryInterface::class                  => RoleRepository::class,
        QuoteDraftedRepositoryInterface::class          => QuoteDraftedRepository::class,
        QuoteSubmittedRepositoryInterface::class        => QuoteSubmittedRepository::class,
        ContractDraftedRepositoryInterface::class       => ContractDraftedRepository::class,
        ContractSubmittedRepositoryInterface::class     => ContractSubmittedRepository::class,
        InvitationRepositoryInterface::class            => InvitationRepository::class,
        ActivityRepositoryInterface::class              => ActivityRepository::class,
        AddressRepositoryInterface::class               => AddressRepository::class,
        ContactRepositoryInterface::class               => ContactRepository::class,
        AuthServiceInterface::class                     => AuthService::class,
        ReportLoggerInterface::class                    => ReportLogger::class,
        NotificationRepositoryInterface::class          => NotificationRepository::class,
        UIServiceInterface::class                       => UIService::class,
        HttpInterface::class                            => HttpService::class,
        ClientCredentialsInterface::class               => ClientCredentialsRepository::class,
        BuildRepositoryInterface::class                 => BuildRepository::class,
        MaintenanceServiceInterface::class              => MaintenanceService::class,
        ExchangeRateRepositoryInterface::class          => ExchangeRateRepository::class,
        ExchangeRateServiceInterface::class             => ER_SERVICE_CLASS,
        'request.filter'                                => RequestQueryFilter::class,
        ContractStateRepositoryInterface::class         => ContractStateRepository::class,
        QuoteNoteRepositoryInterface::class             => QuoteNoteRepository::class,
        TaskRepositoryInterface::class                  => TaskRepository::class,
        PermissionBrokerContract::class                 => PermissionBroker::class,
        UserFormContract::class                         => UserForm::class,
    ];

    public $bindings = [
        SlackInterface::class                           => SlackClient::class,
        \Spatie\Activitylog\ActivityLogger::class       => ActivityLogger::class,
        NotificationInterface::class                    => NotificationStorage::class
    ];

    public $aliases = [
        AuthenticatedCase::class                        => 'auth.case',
        ElasticsearchClient::class                      => 'elasticsearch.client',
        QuoteServiceInterface::class                    => 'quote.service',
        QuoteRepositoryInterface::class                 => 'quote.repository',
        ContractStateRepositoryInterface::class         => 'contract.repository',
        QuoteDraftedRepositoryInterface::class          => 'quote.drafted.repository',
        QuoteSubmittedRepositoryInterface::class        => 'quote.submitted.repository',
        QuoteFileRepositoryInterface::class             => 'quotefile.repository',
        AuthServiceInterface::class                     => 'auth.service',
        \Laravel\Passport\ClientRepository::class       => 'passport.client.repository',
        CountryRepositoryInterface::class               => 'country.repository',
        TimezoneRepositoryInterface::class              => 'timezone.repository',
        CompanyRepositoryInterface::class               => 'company.repository',
        VendorRepositoryInterface::class                => 'vendor.repository',
        UserRepositoryInterface::class                  => 'user.repository',
        RoleRepositoryInterface::class                  => 'role.repository',
        CustomerRepositoryInterface::class              => 'customer.repository',
        MarginRepositoryInterface::class                => 'margin.repository',
        QuoteTemplateRepositoryInterface::class         => 'template.repository',
        ContractTemplateRepositoryInterface::class      => 'contract_template.repository',
        CurrencyRepositoryInterface::class              => 'currency.repository',
        ImportableColumnRepositoryInterface::class      => 'importablecolumn.repository',
        ReportLoggerInterface::class                    => 'report.logger',
        SlackInterface::class                           => 'slack.client',
        SystemSettingRepositoryInterface::class         => 'setting.repository',
        NotificationRepositoryInterface::class          => 'notification.repository',
        NotificationInterface::class                    => 'notification.storage',
        UIServiceInterface::class                       => 'ui.service',
        HttpInterface::class                            => 'http.service',
        ClientCredentialsInterface::class               => 'client.repository',
        BuildRepositoryInterface::class                 => 'build.repository',
        ExchangeRateServiceInterface::class             => 'exchange.service',
        TaskRepositoryInterface::class                  => 'task.repository',
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if (property_exists($this, 'aliases')) {
            foreach ($this->aliases as $key => $value) {
                $this->app->alias($key, $value);
            }
        }

        $this->app->instance('path.storage', config('filesystems.disks.local.path'));

        $this->app->singleton(ElasticsearchClient::class, fn () => ElasticsearchBuilder::create()->setHosts(config('services.search.hosts'))->build());

        $this->app->singleton(QuoteTaskTemplateStore::class, fn () => QuoteTaskTemplateStore::make(storage_path('valuestore/quote.task.template.json')));

        $this->app->when(\Spatie\PdfToText\Pdf::class)->needs('$binPath')->give(config('pdfparser.pdftotext.bin_path'));

        $this->app->tag([QuoteTaskTemplateStore::class], 'task_templates');

        $this->app->bind('task_template.manager', fn ($app) => new TaskTemplateManager($app->tagged('task_templates')));
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        if (env('APP_DEBUG') && app()->environment('local')) {

            DB::listen(function ($query) {
                File::append(
                    storage_path('/logs/query.log'),
                    sprintf("[time: %s] %s\n", $query->time, $query->sql)
                );
            });
        }
    }
}

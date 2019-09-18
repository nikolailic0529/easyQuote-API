<?php namespace App\Providers;

use Laravel\Passport\Passport;
use Laravel\Passport\Client;
use Laravel\Passport\PersonalAccessClient;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;
use Schema;
use App\Http\Controllers\API \ {
    AuthController,
    Quotes\QuoteFilesController
};
use App\Contracts \ {
    Services\AuthServiceInterface,
    Services\ParserServiceInterface,
    Services\WordParserInterface,
    Services\PdfParserInterface,
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
    Repositories\System\SystemSettingRepositoryInterface
};
use App\Repositories \ {
    TimezoneRepository,
    CountryRepository,
    UserRepository,
    AccessAttemptRepository,
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
    System\SystemSettingRepository
};
use App\Services \ {
    AuthService,
    ParserService,
    WordParser,
    PdfParser
};
use Illuminate\Support\Str;

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
        MarginRepositoryInterface::class => MarginRepository::class
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

        $this->app->when(QuoteFilesController::class)->needs(ParserServiceInterface::class)->give(ParserService::class);

        $this->app->when(ParserService::class)->needs(WordParserInterface::class)->give(WordParser::class);

        $this->app->when(ParserService::class)->needs(PdfParserInterface::class)->give(PdfParser::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

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

        Str::macro('header', function ($value) {
            return self::title(str_replace('_', ' ', $value));
        });
    }
}

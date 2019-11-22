<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Laravel\Passport\Client;
use Laravel\Passport\PersonalAccessClient;
use Webpatser\Uuid\Uuid;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\API\AuthController;
use App\Contracts\{
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
    Repositories\System\SystemSettingRepositoryInterface,
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
    Services\QuoteServiceInterface
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
    Quote\Discount\MultiYearDiscountRepository,
    Quote\Discount\PromotionalDiscountRepository,
    Quote\Discount\PrePayDiscountRepository,
    Quote\Discount\SNDrepository,
    Quote\QuoteDraftedRepository,
    Quote\QuoteSubmittedRepository,
    VendorRepository,
    CompanyRepository,
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
use Illuminate\Support\Collection;
use Schema, Storage, Blade, File, Str, Arr, Validator;

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
        InvitationRepositoryInterface::class => InvitationRepository::class
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

        $this->registerMacro();

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

    protected function registerMacro()
    {
        Str::macro('header', function ($value, $default = null, $perform = true) {
            if (!$perform) {
                return $value;
            }

            if (!isset($value)) {
                return $default;
            }

            return self::title(str_replace('_', ' ', $value));
        });

        Str::macro('columnName', function ($value) {
            return self::snake(preg_replace('/\W/', '', $value));
        });

        Str::macro('price', function ($value, $format = null) {
            $value = round((float) preg_replace('/[^\d\.]/', '', $value), 2);

            if (isset($format) && $format) {
                return number_format($value, 2);
            }

            return $value;
        });

        Str::macro('decimal', function ($value) {
            if (!is_string($value) && !is_numeric($value)) {
                return $value;
            }

            return number_format(round((float) $value, 2), 2, '.', '');
        });

        Str::macro('short', function ($value) {
            preg_match_all('/\b[a-zA-Z]/', $value, $matches);
            return implode('', $matches[0]);
        });

        Str::macro('name', function ($value) {
            return self::snake(Str::snake(preg_replace('/[^\w\h]/', ' ', $value)));
        });

        Str::macro('prepend', function (string $value, ?string $prependable) {
            return filled($prependable) ? "{$prependable} {$value}" : $value;
        });

        Collection::macro('exceptEach', function (...$keys) {
            if (!is_iterable((array) head($this->items))) {
                return $this;
            }

            is_iterable(head($keys)) && $keys = head($keys);

            foreach ($this->items as &$item) {
                $item = collect($item)->except($keys);
            }

            return $this;
        });

        File::macro('abspath', function (string $value) {
            return storage_path('app\public' . str_replace(asset('storage'), '', $value));
        });

        Collection::macro('sortKeysByKeys', function (array $keys) {
            return self::transform(function ($row) use ($keys) {
                return array_replace($keys, array_intersect_key((array) $row, $keys));
            });
        });

        Collection::macro('rowsToGroups', function (string $groupable, ?Collection $meta = null, bool $recalculate = false, ?string $currency = null) {
            $groups = $this->groupBy($groupable)->transform(function ($rows, $key) use ($groupable, $meta, $currency) {
                $meta = isset($meta)
                    ? $meta->firstWhere('name', '===', $key) ?? []
                    : [];
                $rows = collect($rows)
                    ->transform(function ($row) use ($currency) {
                        data_set($row, 'computable_price', data_get($row, 'price', 0.0));
                        data_set($row, 'price', Str::prepend(Str::decimal(data_get($row, 'price', 0.0)), $currency));
                        return $row;
                    })
                    ->exceptEach($groupable);

                $headers_count = count($rows->first()) - 1;

                return array_merge((array) $meta, ['headers_count' => $headers_count, $groupable => $key, 'rows' => $rows]);
            })->values();

            filled($meta) && $meta->whereNotIn($groupable, $groups->pluck($groupable))->each(function ($meta) use ($groups) {
                $groups->push(array_merge($meta, ['rows' => collect()]));
            });

            $recalculate && $groups->transform(function ($group) use ($currency) {
                $total_price = Str::decimal($group['rows']->sum('computable_price'));
                data_set($group, 'total_price', Str::prepend($total_price, $currency));
                return $group;
            });

            $groups->transform(function ($group) {
                data_set($group, 'rows', $group['rows']->exceptEach('computable_price'));
                return $group;
            });

            return $groups;
        });

        Arr::macro('lower', function (array $array) {
            return array_map(function ($item) {
                if (!is_string($item)) {
                    return $item;
                }

                return mb_strtolower($item);
            }, $array);
        });

        Arr::macro('quote', function ($value) {
            return implode(',', array_map('json_encode', $value));
        });

        Arr::macro('cols', function (array $value, string $append = '') {
            return implode(', ', array_map(function ($item) use ($append) {
                return "`{$item}`{$append}";
            }, $value));
        });
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

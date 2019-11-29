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
use Spatie\Activitylog\ActivityLogger;
use Schema, File, Str, Arr;

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
        OperationRepositoryInterface::class => OperationRepository::class,
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
        AddressRepositoryInterface::class => AddressRepository::class
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

        Str::macro('price', function ($value, bool $format = false, bool $detectDelimiter = false) {
            if ($detectDelimiter) {
                if (preg_match('/\d+ \d+(,)\d{1,2}/', $value)) {
                    $value = str_replace(',', '.', $value);
                }

                if (!preg_match('/[,\.]/', $value)) {
                    $value = str_replace(',', '', $value);
                }

                if (preg_match('/,/', $value) && !preg_match('/\./', $value)) {
                    $value = str_replace(',', '.', $value);
                }
            }

            $value = (float) preg_replace('/[^\d\.]/', '', $value);

            if ($format) {
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

        Str::macro('prepend', function (string $value, ?string $prependable, bool $noBreak = false) {
            $space = $noBreak ? "\xC2\xA0" : ' ';

            return filled($prependable) ? "{$prependable}{$space}{$value}" : $value;
        });

        Str::macro('formatAttributeKey', function (string $value) {
            $value = static::before($value, '.');

            if (!ctype_lower($value)) {
                $value = preg_replace('/\s+/u', '', ucwords($value));
                $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1 ', $value));
            }

            return ucwords(str_replace(['-', '_'], ' ', $value));
        });

        Str::macro('spaced', function (string $value) {
            if (!ctype_lower($value) && !ctype_upper($value)) {
                $value = preg_replace('/(.)(?=[A-Z])/u', '$1 ', $value);
            }

            return $value;
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
                        data_set($row, 'price', Str::prepend(Str::decimal(data_get($row, 'price', 0.0)), $currency, true));
                        return $row;
                    })
                    ->exceptEach($groupable);

                /**
                 * Count Headers except computable_price
                 */
                $headers_count = $this->wrap($rows->first())->keys()->diff(['computable_price', 'id', 'is_selected'])->count();

                return array_merge((array) $meta, ['headers_count' => $headers_count, $groupable => $key, 'rows' => $rows]);
            })->values();

            filled($meta) && $meta->whereNotIn($groupable, $groups->pluck($groupable))->each(function ($meta) use ($groups) {
                $groups->push(array_merge($meta, ['rows' => collect()]));
            });

            $recalculate && $groups->transform(function ($group) use ($currency) {
                $total_price = Str::decimal($group['rows']->sum('computable_price'));
                data_set($group, 'total_price', Str::prepend($total_price, $currency, true));
                return $group;
            });

            $groups->transform(function ($group) {
                data_set($group, 'rows', $group['rows']->exceptEach('computable_price'));
                return $group;
            });

            return $groups;
        });

        Collection::macro('sortByFields', function (?array $sortable) {
            if (blank($sortable)) {
                return $this;
            }

            return transform($this, function ($items) use ($sortable) {
                return collect($sortable)->reduce(function ($items, $sort) {
                    $descending = data_get($sort, 'direction') === 'desc' ? true : false;
                    return $items->sortBy(data_get($sort, 'name'), SORT_REGULAR, $descending);
                }, $items)->values();
            });
        });

        Collection::macro('toString', function (string $key, ?string $additionalKey = null, string $glue = ', ') {
            if (!isset($additionalKey)) {
                return $this->pluck($key)->implode($glue);
            }

            return $this->map(function ($item) use ($key, $additionalKey) {
                $value = data_get($item, $key);
                $additionalValue = data_get($item, $additionalKey);

                return "{$value} ($additionalValue)";
            })->implode($glue);
        });

        Collection::macro('udiff', function ($items, bool $both = true) {
            return new static(array_udiff($this->items, $this->getArrayableItems($items), function ($first, $second) use ($both) {
                if ($both) {
                    return $first !== $second ? -1 : 0;
                }

                return $first <=> $second;
            }));
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

        Arr::macro('udiff', function (array $array, array $array2, bool $both = true) {
            return array_udiff($array, $array2, function ($first, $second) use ($both) {
                if ($both) {
                    return $first !== $second ? -1 : 0;
                }

                return $first <=> $second;
            });
        });

        Arr::macro('udiffAssoc', function (array $array, array $array2) {
            return array_udiff_assoc($array, $array2, function ($first, $second) {
                if (is_null($first) || is_null($second)) {
                    return $first === $second ? 0 : 1;
                }

                return $first <=> $second;
            });
        });

        Arr::macro('isDifferentAssoc', function (array $array, array $array2) {
            return filled(self::udiffAssoc($array, $array2));
        });

        ActivityLogger::macro('logWhen', function (string $description, $when) {
            return $when ? $this->log($description) : $this->activity;
        });

        ActivityLogger::macro('withAttribute', function (string $attribute, $new, $old) {
            return $this->withProperties(['attributes' => [$attribute => $new], 'old' => [$attribute => $old]]);
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

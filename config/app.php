<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'url_external' => env('APP_URL_EXTERNAL', env('APP_URL', 'http://localhost')),

    'asset_url' => env('ASSET_URL', null),

    'ui_url' => env('UI_URL', 'http://localhost:4000'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeders. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [
        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */

        \App\Foundation\View\Providers\ViewServiceProvider::class,
        \App\Foundation\Validation\Providers\ValidationServiceProvider::class,

        \App\Foundation\Mail\Providers\MailServiceProvider::class,
        // Register the MailLogEventServiceProvider here to be able recording the mail messages even when the mail limit is over
        // It may be worth moving the mail rate limiting to the domain layer?
        \App\Domain\Mail\Providers\MailLogEventServiceProvider::class,
        \App\Foundation\Mail\Providers\MailEventServiceProvider::class,

        \App\Foundation\Support\Providers\HelperServiceProvider::class,
        \App\Foundation\Support\Elasticsearch\Providers\ElasticsearchServiceProvider::class,
        \App\Foundation\Support\Elasticsearch\Providers\ElasticsearchAuthServiceProvider::class,
        \App\Foundation\Http\Providers\HttpServiceProvider::class,
        \App\Foundation\Http\Providers\ResourceServiceProvider::class,
        \App\Foundation\Support\Providers\MacroServiceProvider::class,
        \App\Foundation\Broadcasting\Providers\BroadcastServiceProvider::class,
        \App\Foundation\Http\Route\RouteServiceProvider::class,
        \App\Foundation\Support\Providers\TelescopeServiceProvider::class,
        \App\Foundation\Cache\Providers\CacheServiceProvider::class,

        \App\Domain\Shared\Horizon\Providers\HorizonServiceProvider::class,
        \App\Domain\Shared\Eloquent\Providers\EntityServiceProvider::class,
        \App\Domain\Shared\Eloquent\Providers\PaginationServiceProvider::class,

        \App\Domain\Mail\Providers\MailLogAuthServiceProvider::class,

        \App\Domain\Shared\Ownership\Providers\OwnershipServiceProvider::class,

        \App\Domain\Log\Providers\LogKeeperServiceProvider::class,

        \App\Domain\App\Providers\AppServiceProvider::class,

        \App\Domain\Appointment\Providers\AppointmentAuthServiceProvider::class,
        \App\Domain\Appointment\Providers\AppointmentOwnershipServiceProvider::class,
        \App\Domain\Appointment\Providers\AppointmentServiceProvider::class,

        \App\Domain\Authentication\Providers\AuthenticationServiceProvider::class,
        \App\Domain\Authentication\Providers\AccessServiceProvider::class,

        \App\Domain\Activity\Providers\ActivityServiceProvider::class,
        \App\Domain\Activity\Providers\ActivityAuthServiceProvider::class,

        \App\Domain\Asset\Providers\AssetServiceProvider::class,
        \App\Domain\Asset\Providers\AssetOwnershipServiceProvider::class,
        \App\Domain\Asset\Providers\AssetAuthServiceProvider::class,

        \App\Domain\Build\Providers\BuildServiceProvider::class,

        \App\Domain\Company\Providers\CompanyServiceProvider::class,
        \App\Domain\Company\Providers\CompanyOwnershipServiceProvider::class,
        \App\Domain\Company\Providers\CompanyAuthServiceProvider::class,
        \App\Domain\Company\Providers\CompanyEventServiceProvider::class,

        \App\Domain\Address\Providers\AddressOwnershipServiceProvider::class,
        \App\Domain\Address\Providers\AddressAuthServiceProvider::class,
        \App\Domain\Address\Providers\AddressEventServiceProvider::class,

        \App\Domain\Location\Providers\LocationServiceProvider::class,

        \App\Domain\Contact\Providers\ContactServiceProvider::class,
        \App\Domain\Contact\Providers\ContactOwnershipServiceProvider::class,
        \App\Domain\Contact\Providers\ContactAuthServiceProvider::class,
        \App\Domain\Contact\Providers\ContactEventServiceProvider::class,

        \App\Domain\Country\Providers\CountryServiceProvider::class,
        \App\Domain\Country\Providers\CountryAuthServiceProvider::class,

        \App\Domain\Currency\Providers\CurrencyServiceProvider::class,

        \App\Domain\Rescue\Providers\CustomerServiceProvider::class,
        \App\Domain\Rescue\Providers\CustomerEventServiceProvider::class,

        \App\Domain\Stats\Providers\DashboardServiceProvider::class,
        \App\Domain\Stats\Providers\StatsEventServiceProvider::class,

        \App\Domain\Margin\Providers\MarginServiceProvider::class,
        \App\Domain\Margin\Providers\MarginAuthServiceProvider::class,

        \App\Domain\Discount\Providers\DiscountServiceProvider::class,
        \App\Domain\Discount\Providers\DiscountAuthServiceProvider::class,

        \App\Domain\ExchangeRate\Providers\ExchangeRatesServiceProvider::class,
        \App\Domain\ExchangeRate\Providers\ExchangeRateEventServiceProvider::class,

        \App\Domain\FailureReport\Providers\FailureServiceProvider::class,

        \App\Domain\HpeContract\Providers\HpeContractServiceProvider::class,
        \App\Domain\HpeContract\Providers\HpeContractAuthServiceProvider::class,

        \App\Domain\Invitation\Providers\InvitationServiceProvider::class,
        \App\Domain\Invitation\Providers\InvitationAuthServiceProvider::class,

        \App\Domain\Language\Providers\LanguageServiceProvider::class,

        \App\Domain\Maintenance\Providers\MaintenanceServiceProvider::class,

        \App\Domain\Note\Providers\NoteServiceProvider::class,
        \App\Domain\Note\Providers\NoteOwnershipServiceProvider::class,
        \App\Domain\Note\Providers\NoteAuthServiceProvider::class,
        \App\Domain\Note\Providers\NoteEventServiceProvider::class,

        \App\Domain\Notification\Providers\NotificationServiceProvider::class,
        \App\Domain\Notification\Providers\NotificationAuthServiceProvider::class,
        \App\Domain\Notification\Providers\NotificationEventServiceProvider::class,

        \App\Domain\QuoteFile\Providers\QuoteFileServiceProvider::class,
        \App\Domain\QuoteFile\Providers\QuoteFileAuthServiceProvider::class,
        \App\Domain\QuoteFile\Providers\DocumentMappingEventServiceProvider::class,
        \App\Domain\QuoteFile\Providers\ImportableColumnEventServiceProvider::class,

        \App\Domain\DocumentEngine\Providers\DocumentEngineServiceProvider::class,

        \App\Domain\DocumentProcessing\Providers\SnappyServiceProvider::class,
        \App\Domain\DocumentProcessing\Providers\ParserServiceProvider::class,

        \App\Domain\Authorization\Providers\ModuleServiceProvider::class,
        \App\Domain\Authorization\Providers\PermissionServiceProvider::class,
        \App\Domain\Authorization\Providers\PermissionEventServiceProvider::class,

        \App\Domain\Rescue\Providers\QuoteContractServiceProvider::class,
        \App\Domain\Rescue\Providers\QuoteServiceProvider::class,
        \App\Domain\Rescue\Providers\QuoteEventServiceProvider::class,
        \App\Domain\Rescue\Providers\RescueAuthServiceProvider::class,

        \App\Domain\Settings\Providers\SettingServiceProvider::class,
        \App\Domain\Settings\Providers\SettingsAuthServiceProvider::class,

        \App\Domain\Slack\Providers\SlackServiceProvider::class,

        \App\Domain\Task\Providers\TaskServiceProvider::class,
        \App\Domain\Task\Providers\TaskOwnershipServiceProvider::class,
        \App\Domain\Task\Providers\TaskAuthServiceProvider::class,
        \App\Domain\Task\Providers\TaskEventServiceProvider::class,

        \App\Domain\Template\Providers\TemplateServiceProvider::class,
        \App\Domain\Template\Providers\TemplateAuthServiceProvider::class,
        \App\Domain\Template\Providers\TemplateEventServiceProvider::class,

        \App\Domain\Timezone\Providers\TimezoneServiceProvider::class,

        \App\Domain\UserInterface\Providers\UIServiceProvider::class,

        \App\Domain\User\Providers\UserServiceProvider::class,
        \App\Domain\User\Providers\UserAuthServiceProvider::class,
        \App\Domain\User\Providers\UserEventServiceProvider::class,

        \App\Domain\Team\Providers\TeamEventServiceProvider::class,

        \App\Domain\Vendor\Providers\VendorServiceProvider::class,
        \App\Domain\Vendor\Providers\VendorAuthServiceProvider::class,

        \App\Domain\Authorization\Providers\AuthServiceProvider::class,

        Webpatser\Countries\CountriesServiceProvider::class,
        Maatwebsite\Excel\ExcelServiceProvider::class,
        Intervention\Image\ImageServiceProvider::class,
        Barryvdh\Snappy\ServiceProvider::class,
        Spatie\Geocoder\GeocoderServiceProvider::class,

        \App\Domain\Attachment\Providers\AttachmentServiceProvider::class,
        \App\Domain\Attachment\Providers\AttachmentOwnershipServiceProvider::class,
        \App\Domain\Attachment\Providers\AttachmentEventServiceProvider::class,

        \App\Domain\Formatting\Providers\FormatterServiceProvider::class,

        \App\Domain\UnifiedQuote\Providers\UnifiedQuoteAuthServiceProvider::class,

        \App\Domain\Worldwide\Providers\QuoteServiceProvider::class,
        \App\Domain\Worldwide\Providers\QuoteOwnershipServiceProvider::class,
        \App\Domain\Worldwide\Providers\WorldwideQuoteEventServiceProvider::class,
        \App\Domain\Worldwide\Providers\SalesOrderServiceProvider::class,
        \App\Domain\Worldwide\Providers\SalesOrderEventServiceProvider::class,
        \App\Domain\Worldwide\Providers\OpportunityOwnershipServiceProvider::class,
        \App\Domain\Worldwide\Providers\OpportunityEventServiceProvider::class,
        \App\Domain\Worldwide\Providers\WorldwideAuthServiceProvider::class,
        \App\Domain\Worldwide\Providers\ViewServiceProvider::class,

        \App\Domain\Pipeline\Providers\PipelineAuthServiceProvider::class,
        \App\Domain\Pipeline\Providers\PipelineEventServiceProvider::class,

        \App\Domain\Pipeliner\Providers\PipelinerServiceProvider::class,
        \App\Domain\Pipeliner\Providers\PipelinerAuthServiceProvider::class,
        \App\Domain\Pipeliner\Providers\PipelinerEventServiceProvider::class,

        \App\Domain\VendorServices\Providers\VendorServicesServiceProvider::class,

        \App\Domain\Image\Providers\ImageServiceProvider::class,

        \App\Domain\SalesUnit\Providers\SalesUnitAuthServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Uuid' => Webpatser\Uuid\Uuid::class,
        'Countries' => Webpatser\Countries\CountriesFacade::class,
        'Excel' => Maatwebsite\Excel\Facades\Excel::class,
        'Setting' => \App\Domain\Settings\Facades\Setting::class,
        'ImageIntervention' => Intervention\Image\Facades\Image::class,
        'Maintenance' => \App\Domain\Maintenance\Facades\Maintenance::class,
        'Permission' => \App\Domain\Authorization\Facades\Permission::class,
        'UserForm' => \App\Domain\User\Facades\UserForm::class,
        'Geocoder' => Spatie\Geocoder\Facades\Geocoder::class,
        'CustomerFlow' => \App\Domain\Rescue\Facades\CustomerFlow::class,
    ],
];

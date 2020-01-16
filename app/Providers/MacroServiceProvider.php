<?php

namespace App\Providers;

use App\Mixins\{
    ActivityLoggerMixin,
    ArrMixin,
    CarbonMixin,
    CollectionMixin,
    FileMixin,
    StrMixin
};
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\{
    Str,
    Collection,
    Facades\File,
    Arr,
    Carbon
};
use Spatie\Activitylog\ActivityLogger;
use Illuminate\Support\Facades\Validator;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Str::mixin(new StrMixin);

        Collection::mixin(new CollectionMixin);

        Carbon::mixin(new CarbonMixin);

        Arr::mixin(new ArrMixin);

        File::mixin(new FileMixin);

        ActivityLogger::mixin(new ActivityLoggerMixin);

        Validator::extend('phone', function ($attribute, $value, $parameters) {
            return preg_match('/\+?[\d]+/', $value);
        }, 'Phone must contain only digits.');

        Validator::extend('alpha_spaces', function ($attribute, $value, $parameters) {
            return preg_match('/^[\pL\s]+$/', $value);
        }, 'The :attribute may only contain letters and spaces.');
    }
}

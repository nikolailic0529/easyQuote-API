<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Customer\Customer;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\Task;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class EntityServiceProvider extends ServiceProvider
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
        Relation::morphMap([
            '6c0f3f29-2d00-4174-9ef8-55aa5889a812' => Quote::class,
            '4d6833e8-d018-4934-bfae-e8587f7aec51' => WorldwideQuote::class,
            '9d7c91c4-5308-4a40-b49e-f10ae552e480' => WorldwideQuoteVersion::class,

            // TODO: replace class strings with uuid
            // TODO: update morph types in addressables, contactables, images, model_has_permissions, notifications, tasks
//            '5b2fe950-aa70-4c36-9b1f-1383daecbb18' => Company::class,
//            'a63f9994-248b-4969-b072-b16c99385a95' => Customer::class,
//            '1eac368b-a170-4dae-aabd-f9e0676411ad' => Vendor::class,
//            '50a0a4c9-d769-44d1-a9b7-ee7903d1f13b' => User::class,
//            'b131d524-f345-4295-ab85-e9098cf82fc2' => Task::class,
        ]);
    }
}

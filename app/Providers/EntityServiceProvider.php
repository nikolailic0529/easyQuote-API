<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Customer\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityForm\OpportunityForm;
use App\Models\OpportunityForm\OpportunityFormSchema;
use App\Models\OpportunitySupplier;
use App\Models\Pipeline\Pipeline;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\Task;
use App\Models\Template\SalesOrderTemplate;
use App\Models\Template\TemplateSchema;
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
            '629f4c90-cd1f-479d-b60c-af912fa5fc4a' => Opportunity::class,
            'e6821c91-a534-4018-a256-5f9a71e1f7a7' => OpportunitySupplier::class,
            '4d6833e8-d018-4934-bfae-e8587f7aec51' => WorldwideQuote::class,
            '9d7c91c4-5308-4a40-b49e-f10ae552e480' => WorldwideQuoteVersion::class,
            'd5ac95d7-dcd3-4958-acce-82c9aba2f3cd' => SalesOrderTemplate::class,
            'bd250dc5-a62c-41e5-9aa4-022cf7c86de1' => TemplateSchema::class,
            '8cc6c6ce-1a57-4d51-9557-3e87c285efa1' => Pipeline::class,
            'f904f1d8-3209-4f09-8e28-13d116555e1f' => OpportunityForm::class,
            'eda5b270-8bd8-4809-8ce0-cb6379fe1b01' => OpportunityFormSchema::class,

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

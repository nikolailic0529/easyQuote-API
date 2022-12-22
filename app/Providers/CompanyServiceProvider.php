<?php

namespace App\Providers;

use App\Services\Company\DataEnrichment\SourceCollection;
use App\Services\Company\DataEnrichment\Sources\CompaniesHouseSource;
use App\Services\Company\DataEnrichment\Sources\Source;
use Illuminate\Support\ServiceProvider;

class CompanyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(CompaniesHouseSource::class)
            ->needs('$config')
            ->giveConfig('services.companies_house');

        $this->app->tag([
            CompaniesHouseSource::class,
        ], Source::class);

        $this->app->when(SourceCollection::class)
            ->needs(Source::class)
            ->giveTagged(Source::class);
    }
}

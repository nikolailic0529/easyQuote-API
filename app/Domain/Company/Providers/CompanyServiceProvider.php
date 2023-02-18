<?php

namespace App\Domain\Company\Providers;

use App\Domain\Company\Services\DataEnrichment\SourceCollection;
use App\Domain\Company\Services\DataEnrichment\Sources\CompaniesHouseSource;
use App\Domain\Company\Services\DataEnrichment\Sources\Source;
use App\Domain\Company\Services\PopulateCompanyWebsiteService;
use Illuminate\Contracts\Container\Container;
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

        $this->app->when(PopulateCompanyWebsiteService::class)
            ->needs('$config')
            ->give(static function (Container $container): array {
                $config = $container['config'];

                return [
                    'domains_list_path' => $config['email.domains_list_path'],
                ];
            });
    }
}

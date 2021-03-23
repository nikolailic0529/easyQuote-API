<?php

namespace App\Providers;

use App\Contracts\Repositories\QuoteTemplate\ContractTemplateRepositoryInterface;
use App\Contracts\Repositories\QuoteTemplate\HpeContractTemplate;
use App\Repositories\QuoteTemplate\ContractTemplateRepository;
use App\Repositories\QuoteTemplate\HpeContractTemplateRepository;
use App\Services\Opportunity\OpportunityTemplateService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class TemplateServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ContractTemplateRepositoryInterface::class, ContractTemplateRepository::class);

        $this->app->singleton(HpeContractTemplate::class, HpeContractTemplateRepository::class);

        $this->app->alias(ContractTemplateRepositoryInterface::class, 'contract_template.repository');

        $this->app->alias(HpeContractTemplate::class, 'hpe_contract_template.repository');

        $this->app->singleton(OpportunityTemplateService::class, function (Container $container) {
            return new OpportunityTemplateService(
                $this->app->basePath('storage/valuestore/opportunity.template.json'),
                $this->app->basePath('storage/_valuestore/opportunity.template.json')
            );
        });
    }

    public function provides()
    {
        return [
            ContractTemplateRepositoryInterface::class,
            'contract_template.repository',
            HpeContractTemplate::class,
            'hpe_contract_template.repository',
            OpportunityTemplateService::class,
        ];
    }
}

<?php

namespace App\Domain\Template\Providers;

use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\Contracts\ContractTemplateRepositoryInterface;
use App\Domain\Template\Contracts\HpeContractTemplate;
use App\Domain\Template\Observers\QuoteTemplateObserver;
use App\Domain\Template\Repositories\ContractTemplateRepository;
use App\Domain\Template\Repositories\HpeContractTemplateRepository;
use App\Domain\Worldwide\Services\Opportunity\OpportunityTemplateService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class TemplateServiceProvider extends ServiceProvider
{
    public function register(): void
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

    public function boot(): void
    {
        QuoteTemplate::observe(QuoteTemplateObserver::class);

        ContractTemplate::observe(QuoteTemplateObserver::class);

        \App\Domain\HpeContract\Models\HpeContractTemplate::observe(QuoteTemplateObserver::class);
    }

    public function provides(): array
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

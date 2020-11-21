<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\QuoteTemplate\ContractTemplateRepositoryInterface;
use App\Contracts\Repositories\QuoteTemplate\HpeContractTemplate;
use App\Contracts\Repositories\QuoteTemplate\QuoteTemplateRepositoryInterface;
use App\Contracts\Repositories\QuoteTemplate\TemplateFieldRepositoryInterface;
use App\Repositories\QuoteTemplate\ContractTemplateRepository;
use App\Repositories\QuoteTemplate\HpeContractTemplateRepository;
use App\Repositories\QuoteTemplate\QuoteTemplateRepository;
use App\Repositories\QuoteTemplate\TemplateFieldRepository;

class TemplateServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(QuoteTemplateRepositoryInterface::class, QuoteTemplateRepository::class);

        $this->app->singleton(ContractTemplateRepositoryInterface::class, ContractTemplateRepository::class);

        $this->app->singleton(TemplateFieldRepositoryInterface::class, TemplateFieldRepository::class);

        $this->app->singleton(HpeContractTemplate::class, HpeContractTemplateRepository::class);

        $this->app->alias(QuoteTemplateRepositoryInterface::class, 'template.repository');

        $this->app->alias(ContractTemplateRepositoryInterface::class, 'contract_template.repository');
        
        $this->app->alias(HpeContractTemplate::class, 'hpe_contract_template.repository');
    }

    public function provides()
    {
        return [
            QuoteTemplateRepositoryInterface::class,
            'template.repository',
            ContractTemplateRepositoryInterface::class,
            'contract_template.repository',
            HpeContractTemplate::class,
            'hpe_contract_template.repository',
            TemplateFieldRepositoryInterface::class,
        ];
    }
}

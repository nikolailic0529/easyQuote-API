<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ContractViewService;
use App\Contracts\Services\ContractView;
use App\Services\ContractStateProcessor;
use App\Contracts\Services\ContractState;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Repositories\Quote\ContractDraftedRepository;
use App\Repositories\Quote\ContractSubmittedRepository;
use App\Contracts\Repositories\Contract\ContractDraftedRepositoryInterface;
use App\Contracts\Repositories\Contract\ContractSubmittedRepositoryInterface;
use App\Queries\ContractQueries;

class QuoteContractServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ContractDraftedRepositoryInterface::class, ContractDraftedRepository::class);

        $this->app->singleton(ContractSubmittedRepositoryInterface::class, ContractSubmittedRepository::class);

        $this->app->singleton(ContractState::class, ContractStateProcessor::class);

        $this->app->alias(ContractState::class, 'contract.state');

        $this->app->singleton(ContractView::class, ContractViewService::class);

        $this->app->singleton(ContractQueries::class);
    }

    public function provides()
    {
        return [
            ContractDraftedRepositoryInterface::class,
            ContractSubmittedRepositoryInterface::class,

            ContractState::class,
            'contract.state',

            ContractView::class,
            ContractQueries::class,
        ];
    }
}

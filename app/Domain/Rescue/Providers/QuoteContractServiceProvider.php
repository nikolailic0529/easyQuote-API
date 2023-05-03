<?php

namespace App\Domain\Rescue\Providers;

use App\Domain\Rescue\Contracts\ContractDraftedRepositoryInterface;
use App\Domain\Rescue\Contracts\ContractState;
use App\Domain\Rescue\Contracts\ContractSubmittedRepositoryInterface;
use App\Domain\Rescue\Contracts\ContractView;
use App\Domain\Rescue\Queries\ContractQueries;
use App\Domain\Rescue\Repositories\ContractDraftedRepository;
use App\Domain\Rescue\Repositories\ContractSubmittedRepository;
use App\Domain\Rescue\Services\ContractStateProcessor;
use App\Domain\Rescue\Services\ContractViewService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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

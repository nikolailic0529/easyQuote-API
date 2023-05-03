<?php

namespace App\Domain\Rescue\Providers;

use App\Domain\Rescue\Contracts\QuoteDraftedRepositoryInterface;
use App\Domain\Rescue\Contracts\QuoteState;
use App\Domain\Rescue\Contracts\QuoteSubmittedRepositoryInterface;
use App\Domain\Rescue\Contracts\QuoteView;
use App\Domain\Rescue\Queries\QuoteQueries;
use App\Domain\Rescue\Repositories\QuoteDraftedRepository;
use App\Domain\Rescue\Repositories\QuoteSubmittedRepository;
use App\Domain\Rescue\Services\QuoteStateProcessor;
use App\Domain\Rescue\Services\QuoteViewService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class QuoteServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(QuoteState::class, QuoteStateProcessor::class);

        $this->app->singleton(QuoteView::class, QuoteViewService::class);

        $this->app->singleton(QuoteDraftedRepositoryInterface::class, QuoteDraftedRepository::class);

        $this->app->singleton(QuoteSubmittedRepositoryInterface::class, QuoteSubmittedRepository::class);

        $this->app->singleton(QuoteQueries::class);

        $this->app->alias(QuoteView::class, 'quote.service');

        $this->app->alias(QuoteState::class, 'quote.state');

        $this->app->alias(QuoteDraftedRepositoryInterface::class, 'quote.drafted.repository');

        $this->app->alias(QuoteSubmittedRepositoryInterface::class, 'quote.submitted.repository');
    }

    public function provides(): array
    {
        return [
            QuoteState::class,
            'quote.state',
            QuoteView::class,
            'quote.service',
            QuoteDraftedRepositoryInterface::class,
            'quote.drafted.repository',
            QuoteSubmittedRepositoryInterface::class,
            QuoteQueries::class,
        ];
    }
}

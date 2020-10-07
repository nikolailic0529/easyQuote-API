<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface;
use App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface;
use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface;
use App\Contracts\Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface;
use App\Contracts\Repositories\QuoteFile\FileFormatRepositoryInterface;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface;
use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
use App\Contracts\Services\QuoteServiceInterface;
use App\Contracts\Services\QuoteState;
use App\Repositories\Quote\Margin\MarginRepository;
use App\Repositories\Quote\QuoteDraftedRepository;
use App\Repositories\Quote\QuoteSubmittedRepository;
use App\Repositories\QuoteFile\DataSelectSeparatorRepository;
use App\Repositories\QuoteFile\FileFormatRepository;
use App\Repositories\QuoteFile\ImportableColumnRepository;
use App\Repositories\QuoteFile\QuoteFileRepository;
use App\Services\QuoteService;
use App\Services\QuoteStateProcessor;

class QuoteServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(QuoteFileRepositoryInterface::class, QuoteFileRepository::class);

        $this->app->singleton(FileFormatRepositoryInterface::class, FileFormatRepository::class);

        $this->app->singleton(ImportableColumnRepositoryInterface::class, ImportableColumnRepository::class);

        $this->app->singleton(QuoteState::class, QuoteStateProcessor::class);

        $this->app->singleton(DataSelectSeparatorRepositoryInterface::class, DataSelectSeparatorRepository::class);

        $this->app->singleton(MarginRepositoryInterface::class, MarginRepository::class);

        $this->app->singleton(QuoteServiceInterface::class, QuoteService::class);

        $this->app->singleton(QuoteDraftedRepositoryInterface::class, QuoteDraftedRepository::class);

        $this->app->singleton(QuoteSubmittedRepositoryInterface::class, QuoteSubmittedRepository::class);

        $this->app->alias(QuoteServiceInterface::class, 'quote.service');

        $this->app->alias(QuoteState::class, 'quote.state');

        $this->app->alias(QuoteDraftedRepositoryInterface::class, 'quote.drafted.repository');

        $this->app->alias(QuoteSubmittedRepositoryInterface::class, 'quote.submitted.repository');

        $this->app->alias(QuoteFileRepositoryInterface::class, 'quotefile.repository');

        $this->app->alias(MarginRepositoryInterface::class, 'margin.repository');

        $this->app->alias(ImportableColumnRepositoryInterface::class, 'importablecolumn.repository');
    }

    public function provides()
    {
        return [
            QuoteFileRepositoryInterface::class,
            'quotefile.repository',
            FileFormatRepositoryInterface::class,
            ImportableColumnRepositoryInterface::class,
            'importablecolumn.repository',
            QuoteState::class,
            'quote.state',
            QuoteServiceInterface::class,
            'quote.service',
            DataSelectSeparatorRepositoryInterface::class,
            MarginRepositoryInterface::class,
            'margin.repository',
            QuoteDraftedRepositoryInterface::class,
            'quote.drafted.repository',
            QuoteSubmittedRepositoryInterface::class,
        ];
    }
}

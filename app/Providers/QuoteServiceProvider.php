<?php

namespace App\Providers;

use App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface;
use App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface;
use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface;
use App\Contracts\Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface;
use App\Contracts\Repositories\QuoteFile\FileFormatRepositoryInterface;
use App\Contracts\Repositories\QuoteFile\ImportableColumnRepositoryInterface;
use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
use App\Contracts\Services\ProcessesSalesOrderState;
use App\Contracts\Services\ProcessesWorldwideDistributionState;
use App\Contracts\Services\ProcessesWorldwideQuoteAssetState;
use App\Contracts\Services\ProcessesWorldwideQuoteState;
use App\Contracts\Services\QuoteState;
use App\Contracts\Services\QuoteView;
use App\Queries\QuoteQueries;
use App\Queries\WorldwideQuoteQueries;
use App\Repositories\Quote\Margin\MarginRepository;
use App\Repositories\Quote\QuoteDraftedRepository;
use App\Repositories\Quote\QuoteSubmittedRepository;
use App\Repositories\QuoteFile\DataSelectSeparatorRepository;
use App\Repositories\QuoteFile\FileFormatRepository;
use App\Repositories\QuoteFile\ImportableColumnRepository;
use App\Repositories\QuoteFile\QuoteFileRepository;
use App\Services\QuoteStateProcessor;
use App\Services\QuoteViewService;
use App\Services\SalesOrder\SalesOrderStateProcessor;
use App\Services\WorldwideQuote\WorldwideDistributionStateProcessor;
use App\Services\WorldwideQuote\WorldwideQuoteAssetStateProcessor;
use App\Services\WorldwideQuote\WorldwideQuoteStateProcessor;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

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

        $this->app->singleton(QuoteView::class, QuoteViewService::class);

        $this->app->singleton(QuoteDraftedRepositoryInterface::class, QuoteDraftedRepository::class);

        $this->app->singleton(QuoteSubmittedRepositoryInterface::class, QuoteSubmittedRepository::class);

        $this->app->singleton(ProcessesWorldwideQuoteState::class, WorldwideQuoteStateProcessor::class);

        $this->app->singleton(QuoteQueries::class);

        $this->app->singleton(WorldwideQuoteQueries::class);

        $this->app->singleton(ProcessesWorldwideDistributionState::class, function (Container $container) {

            $storage = $container['filesystem']->disk('ww_quote_files');

            return $container->make(WorldwideDistributionStateProcessor::class, ['storage' => $storage]);

        });

        $this->app->singleton(ProcessesSalesOrderState::class, SalesOrderStateProcessor::class);

        $this->app->alias(QuoteView::class, 'quote.service');

        $this->app->alias(QuoteState::class, 'quote.state');

        $this->app->alias(QuoteDraftedRepositoryInterface::class, 'quote.drafted.repository');

        $this->app->alias(QuoteSubmittedRepositoryInterface::class, 'quote.submitted.repository');

        $this->app->alias(QuoteFileRepositoryInterface::class, 'quotefile.repository');

        $this->app->alias(MarginRepositoryInterface::class, 'margin.repository');

        $this->app->alias(ImportableColumnRepositoryInterface::class, 'importablecolumn.repository');

        $this->app->singleton(ProcessesWorldwideQuoteAssetState::class, function (Container $container) {

            $storage = $container['filesystem']->disk('ww_asset_files');

            return $container->make(WorldwideQuoteAssetStateProcessor::class, ['storage' => $storage]);

        });
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
            QuoteView::class,
            'quote.service',
            DataSelectSeparatorRepositoryInterface::class,
            MarginRepositoryInterface::class,
            'margin.repository',
            QuoteDraftedRepositoryInterface::class,
            'quote.drafted.repository',
            QuoteSubmittedRepositoryInterface::class,
            ProcessesWorldwideQuoteState::class,
            ProcessesWorldwideDistributionState::class,

            QuoteQueries::class,
            WorldwideQuoteQueries::class,
            ProcessesWorldwideQuoteAssetState::class,

            ProcessesSalesOrderState::class
        ];
    }
}

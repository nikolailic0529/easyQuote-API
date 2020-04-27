<?php

namespace App\Repositories\Quote;

use App\Contracts\{
    Repositories\Quote\QuoteSubmittedRepositoryInterface,
    Repositories\QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository,
    Services\QuoteServiceInterface
};
use App\Repositories\{
    SearchableRepository,
    Concerns\ResolvesImplicitModel,
    Concerns\ResolvesQuoteVersion,
};
use App\Http\Resources\QuoteRepository\SubmittedCollection;
use App\Models\Quote\{
    Quote,
    BaseQuote
};
use Closure;
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};
use File, DB, Storage;
use Illuminate\Support\LazyCollection;

class QuoteSubmittedRepository extends SearchableRepository implements QuoteSubmittedRepositoryInterface
{
    use ResolvesImplicitModel, ResolvesQuoteVersion;

    const EXPORT_CACHE_TTL = 60;

    const EXPORT_CACHE_PREFIX = 'quote-pdf';

    /** @var \App\Models\Quote\Quote */
    protected Quote $quote;

    /** @var \App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface */
    protected QuoteFileRepository $quoteFile;

    /** @var \App\Contracts\Services\QuoteServiceInterface */
    protected QuoteServiceInterface $quoteService;

    public function __construct(Quote $quote, QuoteFileRepository $quoteFile, QuoteServiceInterface $quoteService)
    {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->quoteService = $quoteService;
    }

    public function all()
    {
        return $this->toCollection(parent::all());
    }

    public function search(string $query = '')
    {
        return $this->toCollection(parent::search($query));
    }

    public function cursor(?Closure $scope = null): LazyCollection
    {
        return $this->quote
            ->on('mysql_unbuffered')
            ->submitted()
            ->when($scope, $scope)
            ->cursor();
    }

    public function count(array $where = []): int
    {
        return $this->quote->submitted()->where($where)->count();
    }

    public function userQuery(): Builder
    {
        $user = auth()->user();

        return $this->quote
            ->when(
                /** If user is not super-admin we are retrieving the user's own quotes */
                $user->cant('view_quotes'),
                fn (Builder $query) => $query->currentUser()
                    /** Adding quotes that have been granted access to */
                    ->orWhereIn('id', $user->getPermissionTargets('quotes.read'))
                    ->orWhereIn('user_id', $user->getModulePermissionProviders('quotes.read'))
            )
            ->with('usingVersion.quoteFiles')
            ->submitted();
    }

    public function toCollection($resource): SubmittedCollection
    {
        return SubmittedCollection::make($resource);
    }

    public function findByRfq(string $rfq): BaseQuote
    {
        $quote = $this->quote->submitted()->activated()->orderByDesc('submitted_at')->rfq($rfq)->first();

        error_abort_if(is_null($quote), EQ_NF_01, 'EQ_NF_01', 422);

        return $quote;
    }

    public function find(string $id): Quote
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function rfq(string $rfq, bool $serviceCaused = false): BaseQuote
    {
        $quote = $this->findByRfq($rfq);

        if ($serviceCaused) {
            activity()->on($quote)->causedByService(request('client_name', 'Service'))->queue('retrieved');
        }

        $quote = $quote->usingVersion->disableReview();

        $this->quoteService->prepareQuoteReview($quote);

        return $quote;
    }

    public function price(string $rfq)
    {
        $quote = $this->findByRfq($rfq);
        $priceList = $quote->usingVersion->priceList;

        $path = $priceList->original_file_path;
        $storagePath = $this->resolveFilepath($priceList->original_file_path);

        if ($priceList->isCsv()) {
            $csvPath = File::dirname($path) . DIRECTORY_SEPARATOR . File::name($path) . '.csv';

            Storage::missing($csvPath) && Storage::copy($path, $csvPath);

            return $this->resolveFilepath($csvPath);
        }

        return $storagePath;
    }

    public function schedule(string $rfq)
    {
        $quote = $this->findByRfq($rfq);
        $paymentSchedule = $quote->usingVersion->paymentSchedule;

        return $this->resolveFilepath($paymentSchedule->original_file_path);
    }

    public function pdf(string $rfq)
    {
        $quote = $this->findByRfq($rfq);

        return $this->retrieveCachedQuotePdf($quote);
    }

    public function exportPdf($quote, string $type = QT_TYPE_QUOTE)
    {
        $quote = $this->resolveModel($quote);

        return $this->retrieveCachedQuotePdf($quote->switchModeTo($type));
    }

    public function delete(string $id)
    {
        return $this->find($id)->delete();
    }

    public function activate(string $id)
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id)
    {
        return $this->find($id)->deactivate();
    }

    public function unSubmit(string $id): bool
    {
        return $this->find($id)->unsubmit();
    }

    public function copy($quote)
    {
        $quote = $this->resolveModel($quote);
        $version = $this->resolveQuoteVersion($quote, $quote->usingVersion);

        return DB::transaction(function () use ($quote, $version) {
            $replicatedQuote = $version->replicate(['laravel_through_key']);
            $replicatedQuote->is_version = false;

            $quote->deactivate();

            $replicatedQuote->unSubmit();
            $pass = $replicatedQuote->save();

            /**
             * Discounts Replication
             */
            $discounts = DB::table('quote_discount')
                ->select(DB::raw("'{$replicatedQuote->id}' `quote_id`"), 'discount_id', 'duration')
                ->where('quote_id', $quote->id);
            DB::table('quote_discount')->insertUsing(['quote_id', 'discount_id', 'duration'], $discounts);

            /**
             * Mapping Replication
             */
            $mapping = DB::table('quote_field_column')
                ->select(DB::raw("'{$replicatedQuote->id}' as `quote_id`"), 'template_field_id', 'importable_column_id', 'is_default_enabled')
                ->where('quote_id', $quote->id);
            DB::table('quote_field_column')->insertUsing(
                ['quote_id', 'template_field_id', 'importable_column_id', 'is_default_enabled'],
                $mapping
            );

            $quoteFilesToSave = collect();

            $priceList = $quote->quoteFiles()->priceLists()->first();
            if (isset($priceList)) {
                $quoteFilesToSave->push($this->quoteFile->replicatePriceList($priceList));
            }

            $schedule = $quote->quoteFiles()->paymentSchedules()->with('scheduleData')->first();
            if (isset($schedule)) {
                $replicatedSchedule = $schedule->replicate();
                unset($replicatedSchedule->scheduleData);
                $replicatedSchedule->save();

                if (isset($schedule->scheduleData)) {
                    $replicatedSchedule->scheduleData()->save($schedule->scheduleData->replicate());
                }

                $quoteFilesToSave->push($replicatedSchedule);
            }

            $copied = $pass && $replicatedQuote->quoteFiles()->saveMany($quoteFilesToSave);

            if ($copied) {
                activity()
                    ->on($replicatedQuote)
                    ->withProperties(['old' => Quote::logChanges($version), 'attributes' => Quote::logChanges($replicatedQuote)])
                    ->by(request()->user())
                    ->queue('copied');
            }

            return $copied;
        });
    }

    public function setContractTemplate(string $id, string $contract_template_id): bool
    {
        return $this->find($id)->update(compact('contract_template_id'));
    }

    public function model(): string
    {
        return Quote::class;
    }

    public function flushQuotePdfCache(Quote $quote): void
    {
        collect(QT_TYPES)->each(fn ($type) => cache()->forget($this->quotePdfCacheKey($quote, $type)));
    }

    protected function resolveFilepath($path)
    {
        abort_if(blank($path) || Storage::missing($path), 404, QFNE_01);

        return Storage::path($path);
    }

    protected function filterQueryThrough(): array
    {
        return [
            app(\App\Http\Query\DefaultOrderBy::class, ['column' => 'updated_at']),
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\Quote\OrderByName::class,
            \App\Http\Query\Quote\OrderByCompanyName::class,
            \App\Http\Query\Quote\OrderByRfq::class,
            \App\Http\Query\Quote\OrderByValidUntil::class,
            \App\Http\Query\Quote\OrderBySupportStart::class,
            \App\Http\Query\Quote\OrderBySupportEnd::class,
            \App\Http\Query\Quote\OrderByCompleteness::class
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->activated()->with('contract.customer'),
            $this->userQuery()->deactivated()->with('contract.customer')
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->quote;
    }

    protected function searchableQuery()
    {
        return $this->userQuery()->with('contract.customer');
    }

    protected function searchableFields(): array
    {
        return [
            'company_name^5',
            'customer_name^5',
            'customer_rfq^5',
            'customer_valid_until^4',
            'customer_support_start^4',
            'customer_support_end^4',
            'user_fullname^4',
            'created_at^1'
        ];
    }

    protected function searchableScope($query)
    {
        return $query;
    }

    private function retrieveCachedQuotePdf(Quote $quote)
    {
        return cache()->remember(
            $this->quotePdfCacheKey($quote),
            static::EXPORT_CACHE_TTL,
            fn () => $this->quoteService->export(
                tap($quote->usingVersion)->switchModeTo($quote->mode)
            )
        );
    }

    private function quotePdfCacheKey(Quote $quote): string
    {
        return static::EXPORT_CACHE_PREFIX . '-' . $quote->mode . ':' . $quote->id;
    }
}

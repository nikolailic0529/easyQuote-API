<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\{
    Quote\QuoteSubmittedRepositoryInterface,
    QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository
};
use App\Contracts\Services\QuoteServiceInterface as QuoteService;
use App\Repositories\SearchableRepository;
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};
use DB, Storage;

class QuoteSubmittedRepository extends SearchableRepository implements QuoteSubmittedRepositoryInterface
{
    protected $quote;

    protected $quoteFile;

    protected $quoteService;

    public function __construct(Quote $quote, QuoteFileRepository $quoteFile, QuoteService $quoteService)
    {
        $this->quote = $quote;
        $this->quoteFile = $quoteFile;
        $this->quoteService = $quoteService;
    }

    public function userQuery(): Builder
    {
        return $this->quote->query()->currentUser()->submitted()->with('customer', 'company');
    }

    public function findByRfq(string $rfq): Quote
    {
        return $this->quote->submitted()->orderBy('updated_at', 'desc')->rfq($rfq)->firstOrFail();
    }

    public function find(string $id): Quote
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function rfq(string $rfq): array
    {
        $quote = $this->findByRfq($rfq);

        blank($quote->submitted_data) && abort('404', __('quote.no_found_rfq_exception'));

        return $quote->submitted_data;
    }

    public function price(string $rfq)
    {
        $path = $this->findByRfq($rfq)->priceList->original_file_path;
        $storage_path = Storage::path($path);

        (blank($path) || Storage::exists($storage_path)) && abort('404', __('quote_file.not_exists_exception'));

        return $storage_path;
    }

    public function schedule(string $rfq)
    {
        $path = $this->findByRfq($rfq)->paymentSchedule->original_file_path;
        $storage_path = Storage::path($path);

        (blank($path) || Storage::exists($storage_path)) && abort('404', __('quote_file.not_exists_exception'));

        return $storage_path;
    }

    public function pdf(string $rfq)
    {
        $path = $this->findByRfq($rfq)->generatedPdf->original_file_path;
        $storage_path = Storage::path($path);

        (blank($path) || Storage::exists($storage_path)) && abort('404', __('quote_file.not_exists_exception'));

        return $storage_path;
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

    public function copy(string $id)
    {
        $quote = $this->find($id)->load('company', 'vendor', 'country', 'discounts', 'customer');

        $replicatedQuote = $quote->replicate();

        $pass = $replicatedQuote->unSubmit();

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

        return $pass && $replicatedQuote->quoteFiles()->saveMany($quoteFilesToSave);
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
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
            $this->userQuery()->activated(),
            $this->userQuery()->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->quote;
    }

    protected function searchableFields(): array
    {
        return [
            'customer.name^5',
            'customer.valid_until^3',
            'customer.support_start^4',
            'customer.support_end^4',
            'customer.rfq^5',
            'company.name^5',
            'type^2',
            'created_at^1'
        ];
    }

    protected function searchableScope(Builder $query)
    {
        return $query->currentUser()->with('customer', 'company');
    }
}
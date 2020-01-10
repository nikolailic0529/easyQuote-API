<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\{
    Quote\QuoteSubmittedRepositoryInterface,
    QuoteFile\QuoteFileRepositoryInterface as QuoteFileRepository
};
use App\Http\Resources\QuoteRepository\SubmittedCollection;
use App\Repositories\SearchableRepository;
use App\Models\Quote\Quote;
use App\Models\Quote\BaseQuote;
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Arr, File, DB, Storage;

class QuoteSubmittedRepository extends SearchableRepository implements QuoteSubmittedRepositoryInterface
{
    protected $quote;

    protected $table;

    protected $quoteFile;

    protected $quoteService;

    public function __construct(Quote $quote, QuoteFileRepository $quoteFile)
    {
        $this->quote = $quote;
        $this->table = $quote->getTable();
        $this->quoteFile = $quoteFile;
    }

    public function all()
    {
        return $this->toCollection(parent::all());
    }

    public function search(string $query = '')
    {
        return $this->toCollection(parent::search($query));
    }

    public function userQuery(): Builder
    {
        return $this->quote
            ->currentUserWhen(request()->user()->cant('view_quotes'))
            ->submitted();
    }

    public function dbQuery(): DatabaseBuilder
    {
        return $this->quote->query()
            ->currentUserWhen(request()->user()->cant('view_quotes'))
            ->toBase()
            ->whereNotNull("{$this->table}.submitted_at")
            ->join('users as user', 'user.id', '=', "{$this->table}.user_id")
            ->join('customers as customer', 'customer.id', '=', "{$this->table}.customer_id")
            ->leftJoin('companies as company', 'company.id', '=', "{$this->table}.company_id")
            ->select([
                "{$this->table}.id",
                "{$this->table}.customer_id",
                "{$this->table}.company_id",
                "{$this->table}.user_id",
                "{$this->table}.completeness",
                "{$this->table}.created_at",
                "{$this->table}.activated_at",
                "customer.name as customer_name",
                "customer.rfq as customer_rfq",
                "customer.valid_until as customer_valid_until",
                "customer.support_start as customer_support_start",
                "customer.support_end as customer_support_end",
                "company.name as company_name",
                "user.first_name as user_first_name",
                "user.last_name as user_last_name"
            ])
            ->groupBy("{$this->table}.id");
    }

    public function toCollection($resource): SubmittedCollection
    {
        return SubmittedCollection::make($resource);
    }

    public function findByRfq(string $rfq): BaseQuote
    {
        $quote = $this->quote->submitted()->activated()->orderByDesc('submitted_at')->rfq($rfq)->first();

        error_abort_if(is_null($quote) || blank($quote->usingVersion->submitted_data), EQ_NF_01, 'EQ_NF_01', 404);

        return $quote->usingVersion;
    }

    public function find(string $id): Quote
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function rfq(string $rfq): iterable
    {
        $quote = $this->findByRfq($rfq);

        $submitted_data = Arr::sortRecursive($quote->submitted_data);

        return $submitted_data;
    }

    public function price(string $rfq)
    {
        $priceList = $this->findByRfq($rfq)->priceList;

        $path = $priceList->original_file_path;
        $storage_path = Storage::path($path);

        (blank($path) || Storage::missing($path)) && abort('404', __('quote_file.not_exists_exception'));

        if ($priceList->isCsv()) {
            $csvPath = File::dirname($path) . DIRECTORY_SEPARATOR . File::name($path) . '.csv';

            Storage::missing($csvPath) && Storage::copy($path, $csvPath);

            return Storage::path($csvPath);
        }

        return $storage_path;
    }

    public function schedule(string $rfq)
    {
        $path = $this->findByRfq($rfq)->paymentSchedule->original_file_path;
        $storage_path = Storage::path($path);

        (blank($path) || Storage::missing($path)) && abort('404', __('quote_file.not_exists_exception'));

        return $storage_path;
    }

    public function pdf(string $rfq)
    {
        $path = $this->findByRfq($rfq)->generatedPdf->original_file_path;
        $storage_path = Storage::path($path);

        (blank($path) || Storage::missing($path)) && abort('404', __('quote_file.not_exists_exception'));

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

    public function unSubmit(string $id): bool
    {
        $quote = $this->find($id);

        return $quote->unSubmit() && optional($quote->customer)->unSubmit();
    }

    public function copy($quote)
    {
        if (is_string($quote)) {
            $quote = $this->find($quote);
        }

        if (!$quote instanceof Quote) {
            throw new \InvalidArgumentException(INV_ARG_QPK_01);
        }

        return DB::transaction(function () use ($quote) {
            $replicatedQuote = $quote->usingVersion->replicate(['laravel_through_key']);
            $replicatedQuote->is_version = false;

            $quote->deactivate();

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

            $copied = $pass && $replicatedQuote->quoteFiles()->saveMany($quoteFilesToSave);

            if ($copied) {
                activity()
                    ->on($replicatedQuote)
                    ->withProperties(['old' => Quote::logChanges($quote->usingVersion), 'attributes' => Quote::logChanges($replicatedQuote)])
                    ->by(request()->user())
                    ->log('copied');
            }

            return $copied;
        });
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

    protected function searchableQuery()
    {
        return $this->userQuery();
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

    protected function searchableScope($query)
    {
        return $query;
    }
}

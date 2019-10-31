<?php namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface;
use App\Repositories\SearchableRepository;
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent \ {
    Model,
    Builder
};
use DB;

class QuoteSubmittedRepository extends SearchableRepository implements QuoteSubmittedRepositoryInterface
{
    protected $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    public function userQuery(): Builder
    {
        return $this->quote->userCollaboration()->submitted()->with('customer', 'company');
    }

    public function find(string $id): Quote
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
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
        $replicatedQuote->user_id = request()->user()->id;

        $pass = $replicatedQuote->push() && $replicatedQuote->unSubmit();

        /**
         * Mapping Replication
         */
        DB::insert("
            insert into `quote_field_column` (quote_id, template_field_id, importable_column_id, is_default_enabled)
            select '{$replicatedQuote->id}' quote_id, template_field_id, importable_column_id, is_default_enabled
            from `quote_field_column` where quote_id = '{$quote->id}'
        ");

        $quoteFilesToSave = collect();

        $priceList = $quote->quoteFiles()->priceLists()->first();
        if(isset($priceList)) {
            $quoteFilesToSave->push($this->quoteFileRepository->replicatePriceList($priceList));
        }

        $schedule = $quote->quoteFiles()->paymentSchedules()->with('scheduleData')->first();
        if(isset($schedule)) {
            $replicatedSchedule = $schedule->replicate();
            unset($replicatedSchedule->scheduleData);
            $replicatedSchedule->save();
            if(isset($schedule->scheduleData)) {
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
        return $query->userCollaboration()->with('customer', 'company');
    }
}

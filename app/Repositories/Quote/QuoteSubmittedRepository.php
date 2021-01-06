<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteSubmittedRepositoryInterface;
use App\Repositories\{
    SearchableRepository,
    Concerns\ResolvesImplicitModel,
    Concerns\ResolvesTargetModel,
};
use App\Http\Resources\QuoteRepository\SubmittedCollection;
use App\Models\Company;
use App\Models\Customer\Customer;
use App\Models\Quote\{
    Quote,
    BaseQuote,
    Contract
};
use App\Models\QuoteFile\QuoteFile;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\{LazyCollection, Facades\File, Facades\Storage};

class QuoteSubmittedRepository extends SearchableRepository implements QuoteSubmittedRepositoryInterface
{
    use ResolvesImplicitModel, ResolvesTargetModel;

    protected Quote $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
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
        /** @var \App\Models\User */
        $user = auth()->user();

        return Quote::query()
            ->when(
                /** If user is not super-admin we are retrieving the user's own quotes */
                $user->cant('view_quotes'),
                fn (Builder $query) => $query->currentUser()
                    /** Adding quotes that have been granted access to */
                    ->orWhereIn('id', $user->getPermissionTargets('quotes.read'))
                    ->orWhereIn('user_id', $user->getModulePermissionProviders('quotes.read'))
            )
            ->join('customers', function (JoinClause $join) {
                $join->on('customers.id', 'quotes.customer_id');
            })
            ->leftJoin('contracts', function (JoinClause $join) {
                $join->on('contracts.quote_id', '=', 'quotes.id')->whereNull('contracts.deleted_at');
            })
            ->select(
                'quotes.id',
                'quotes.active_version_id',
                'quotes.user_id',
                'quotes.company_id',
                'quotes.customer_id',
                'quotes.distributor_file_id',
                'quotes.schedule_file_id',
                'quotes.contract_template_id',
                'quotes.completeness',
                'quotes.created_at',
                'quotes.updated_at',
                'quotes.activated_at'
            )
            ->addSelect([
                'company_name' => Company::select('name')->whereColumn('companies.id', 'quotes.company_id'),
                'user_fullname' => User::select('user_fullname')->whereColumn('users.id', 'quotes.user_id')->limit(1),

                'customers.name as customer_name',
                'customers.rfq as customer_rfq_number',
                'customers.source as customer_source',
                'customers.valid_until as customer_valid_until_date',
                'customers.support_start as customer_support_start_date',
                'customers.support_end as customer_support_end_date',

                'contracts.id as contract_id',
                'contracts.contract_number as contract_number',
                'contracts.submitted_at as contract_submitted_at',

                'price_list_original_file_name' => QuoteFile::select('original_file_name')->whereColumn('quote_files.id', 'quotes.distributor_file_id')->limit(1),
                'payment_schedule_original_file_name' => QuoteFile::select('original_file_name')->whereColumn('quote_files.id', 'quotes.schedule_file_id')->limit(1),

            ])
            ->with([
                'activeVersion' => fn ($query) => $query->select('id', 'quote_id', 'user_id', 'distributor_file_id', 'schedule_file_id', 'company_id', 'version_number', 'completeness', 'updated_at')
                    ->addSelect([
                        'company_name' => Company::select('name')->whereColumn('companies.id', 'quote_versions.company_id')->limit(1),
                        'price_list_original_file_name' => QuoteFile::select('original_file_name')->whereColumn('quote_files.id', 'quote_versions.distributor_file_id')->limit(1),
                        'payment_schedule_original_file_name' => QuoteFile::select('original_file_name')->whereColumn('quote_files.id', 'quote_versions.schedule_file_id')->limit(1)
                    ]),
            ])
            ->whereNotNull('quotes.submitted_at');
    }

    public function toCollection($resource): SubmittedCollection
    {
        return SubmittedCollection::make($resource);
    }

    public function findByRFQ(string $rfq): BaseQuote
    {
        $quote = $this->quote->query()
            ->whereNotNull('submitted_at')
            ->whereNotNull('activated_at')
            ->rfq($rfq)
            ->first();

        error_abort_if(is_null($quote), EQ_NF_01, 'EQ_NF_01', 422);

        return $quote;
    }

    public function find(string $id): Quote
    {
        return $this->quote->query()
            ->whereKey($id)
            ->firstOrFail();
    }

    public function batchUpdate(array $values, array $where = []): bool
    {
        return $this->quote->whereNotNull('submitted_at')->where($where)->update($values);
    }

    public function price(string $rfq)
    {
        $quote = $this->findByRfq($rfq);
        $priceList = ($quote->activeVersionOrCurrent)->priceList;

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
        $paymentSchedule = ($quote->activeVersionOrCurrent)->paymentSchedule;

        return $this->resolveFilepath($paymentSchedule->original_file_path);
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

    public function setContractTemplate(string $id, string $contract_template_id): bool
    {
        return $this->find($id)->update(compact('contract_template_id'));
    }

    public function model(): string
    {
        return Quote::class;
    }

    protected function resolveFilepath($path)
    {
        abort_if(blank($path) || Storage::missing($path), 404, QFNE_01);

        return Storage::path($path);
    }

    protected function filterQueryThrough(): array
    {
        return [
            new \App\Http\Query\ActiveFirst('quotes.is_active'),
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\Quote\OrderByName::class,
            \App\Http\Query\Quote\OrderByCompanyName::class,
            \App\Http\Query\Quote\OrderByRfq::class,
            \App\Http\Query\Quote\OrderByValidUntil::class,
            \App\Http\Query\Quote\OrderBySupportStart::class,
            \App\Http\Query\Quote\OrderBySupportEnd::class,
            \App\Http\Query\Quote\OrderByCompleteness::class,
            new \App\Http\Query\DefaultOrderBy('quotes.updated_at'),
        ];
    }

    protected function filterableQuery()
    {
        return $this->userQuery();
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
            'company_name^5',
            'customer_name^5',
            'customer_rfq^5',
            'customer_valid_until^4',
            'customer_support_start^4',
            'customer_support_end^4',
            'customer_source^4',
            'user_fullname^4',
            'created_at^1'
        ];
    }

    protected function searchableScope($query)
    {
        return $query;
    }
}

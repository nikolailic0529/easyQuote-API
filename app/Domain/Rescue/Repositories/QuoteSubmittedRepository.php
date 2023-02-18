<?php

namespace App\Domain\Rescue\Repositories;

use App\Domain\Company\Models\Company;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Rescue\Contracts\QuoteSubmittedRepositoryInterface;
use App\Domain\Rescue\Resources\V1\SubmittedCollection;
use App\Domain\Shared\Eloquent\Repository\Concerns\ResolvesImplicitModel;
use App\Domain\Shared\Eloquent\Repository\Concerns\ResolvesTargetModel;
use App\Domain\User\Models\User;
use App\Repositories\{App\Foundation\Database\Eloquent\Repository\SearchableRepository};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;

class QuoteSubmittedRepository extends \App\Domain\Shared\Eloquent\Repository\SearchableRepository implements QuoteSubmittedRepositoryInterface
{
    use ResolvesImplicitModel;
    use ResolvesTargetModel;

    protected \App\Domain\Rescue\Models\Quote $quote;

    public function __construct(\App\Domain\Rescue\Models\Quote $quote)
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

    public function cursor(?\Closure $scope = null): LazyCollection
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
        /** @var \App\Domain\User\Models\User $user */
        $user = auth()->user();

        return \App\Domain\Rescue\Models\Quote::query()
            ->when(
                /* If user is not super-admin we are retrieving the user's own quotes */
                false === $user->hasRole(R_SUPER),
                function (Builder $builder) use ($user) {
                    $builder->where(function (Builder $builder) use ($user) {
                        $builder->where('quotes.user_id', auth()->id())
                            /* Adding quotes that have been granted access to */
                            ->orWhereIn($builder->qualifyColumn('id'), $user->getPermissionTargets('quotes.read'))
                            ->orWhereIn($builder->qualifyColumn('user_id'), $user->getModulePermissionProviders('quotes.read'))
                            ->orWhereIn($builder->qualifyColumn('user_id'), $user->ledTeamUsers()->getQuery()->select($user->ledTeamUsers()->getRelated()->getQualifiedKeyName()));
                    });
                }
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
                        'payment_schedule_original_file_name' => QuoteFile::select('original_file_name')->whereColumn('quote_files.id', 'quote_versions.schedule_file_id')->limit(1),
                    ]),
            ])
            ->whereNotNull('quotes.submitted_at');
    }

    public function toCollection($resource): SubmittedCollection
    {
        return SubmittedCollection::make($resource);
    }

    public function findByRFQ(string $rfq): \App\Domain\Rescue\Models\BaseQuote
    {
        $quote = $this->quote->query()
            ->whereNotNull('submitted_at')
            ->whereNotNull('activated_at')
            ->rfq($rfq)
            ->first();

        error_abort_if(is_null($quote), EQ_NF_01, 'EQ_NF_01', 422);

        return $quote;
    }

    public function find(string $id): \App\Domain\Rescue\Models\Quote
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
        $priceList = $quote->activeVersionOrCurrent->priceList;

        $path = $priceList->original_file_path;
        $storagePath = $this->resolveFilepath($priceList->original_file_path);

        if ($priceList->isCsv()) {
            $csvPath = File::dirname($path).DIRECTORY_SEPARATOR.File::name($path).'.csv';

            Storage::missing($csvPath) && Storage::copy($path, $csvPath);

            return $this->resolveFilepath($csvPath);
        }

        return $storagePath;
    }

    public function schedule(string $rfq)
    {
        $quote = $this->findByRfq($rfq);
        $paymentSchedule = $quote->activeVersionOrCurrent->paymentSchedule;

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
        return \App\Domain\Rescue\Models\Quote::class;
    }

    protected function resolveFilepath($path)
    {
        abort_if(blank($path) || Storage::missing($path), 404, QFNE_01);

        return Storage::path($path);
    }

    protected function filterQueryThrough(): array
    {
        return [
            new \App\Domain\Rescue\Queries\Filters\ActiveFirst('quotes.is_active'),
            \App\Domain\Rescue\Queries\Filters\OrderByCreatedAt::class,
            \App\Domain\Rescue\Queries\Filters\OrderByName::class,
            \App\Domain\Rescue\Queries\Filters\OrderByCompanyName::class,
            \App\Domain\Rescue\Queries\Filters\OrderByRfq::class,
            \App\Domain\Rescue\Queries\Filters\OrderByValidUntil::class,
            \App\Domain\Rescue\Queries\Filters\OrderBySupportStart::class,
            \App\Domain\Rescue\Queries\Filters\OrderBySupportEnd::class,
            \App\Domain\Rescue\Queries\Filters\OrderByCompleteness::class,
            new \App\Foundation\Database\Eloquent\QueryFilter\DefaultOrderBy(column: 'quotes.updated_at', ignoreColumn: 'quotes.is_active'),
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
            'created_at^1',
        ];
    }

    protected function searchableScope($query)
    {
        return $query;
    }
}

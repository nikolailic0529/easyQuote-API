<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface;
use App\Http\Resources\QuoteRepository\DraftedCollection;
use App\Models\{Company, Quote\Quote, Quote\QuoteVersion, User};
use App\Models\QuoteFile\QuoteFile;
use App\Repositories\SearchableRepository;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Database\Eloquent\{Builder, Collection, Model};
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\LazyCollection;

class QuoteDraftedRepository extends SearchableRepository implements QuoteDraftedRepositoryInterface
{
    protected Quote $quote;

    protected QuoteVersion $quoteVersion;

    public function __construct(Quote $quote, QuoteVersion $quoteVersion)
    {
        $this->quote = $quote;
        $this->quoteVersion = $quoteVersion;
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
            ->drafted()
            ->when($scope, $scope)
            ->cursor();
    }

    public function userQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return Quote::query()
            ->when(
            /** If user is not super-admin we are retrieving the user's own quotes */
                false === $user->hasRole(R_SUPER),
                function (Builder $builder) use ($user) {
                    $builder->where(function (Builder $builder) use ($user) {
                        $builder->where('quotes.user_id', auth()->id())
                            /** Adding quotes that have been granted access to */
                            ->orWhereIn($builder->qualifyColumn('id'), $user->getPermissionTargets('quotes.read'))
                            ->orWhereIn($builder->qualifyColumn('user_id'), $user->getModulePermissionProviders('quotes.read'))
                            ->orWhereIn($builder->qualifyColumn('user_id'), $user->ledTeamUsers()->getQuery()->select($user->ledTeamUsers()->getRelated()->getQualifiedKeyName()));
                    });
                }
            )
            ->select(
                'quotes.id',
                'quotes.active_version_id',
                'quotes.user_id',
                'quotes.company_id',
                'quotes.customer_id',
                'quotes.completeness',
                'quotes.distributor_file_id',
                'quotes.schedule_file_id',
                'quotes.created_at',
                'quotes.updated_at',
                'quotes.activated_at'
            )
            ->join('customers', function (JoinClause $join) {
                $join->on('customers.id', 'quotes.customer_id');
            })
            ->addSelect([
                'company_name' => Company::query()->select('name')->whereColumn('companies.id', 'quotes.company_id'),
                'user_fullname' => User::query()->select('user_fullname')->whereColumn('users.id', 'quotes.user_id')->limit(1),

                'customers.name as customer_name',
                'customers.rfq as customer_rfq_number',
                'customers.source as customer_source',
                'customers.valid_until as customer_valid_until_date',
                'customers.support_start as customer_support_start_date',
                'customers.support_end as customer_support_end_date',

                'price_list_original_file_name' => QuoteFile::query()->select('original_file_name')->whereColumn('quote_files.id', 'quotes.distributor_file_id')->limit(1),
                'payment_schedule_original_file_name' => QuoteFile::query()->select('original_file_name')->whereColumn('quote_files.id', 'quotes.schedule_file_id')->limit(1),

            ])
            ->with([
                'versions' => fn($query) => $query->select('id', 'quote_id', 'user_id', 'company_id', 'version_number', 'completeness', 'updated_at')
                    ->addSelect(['user_fullname' => User::query()->select('user_fullname')->whereColumn('users.id', 'quote_versions.user_id')->limit(1)]),

                'activeVersion' => fn($query) => $query->select('id', 'quote_id', 'user_id', 'distributor_file_id', 'schedule_file_id', 'company_id', 'version_number', 'completeness', 'updated_at')
                    ->addSelect([
                        'company_name' => Company::query()->select('name')->whereColumn('companies.id', 'quote_versions.company_id')->limit(1),
                        'price_list_original_file_name' => QuoteFile::query()->select('original_file_name')->whereColumn('quote_files.id', 'quote_versions.distributor_file_id')->limit(1),
                        'payment_schedule_original_file_name' => QuoteFile::query()->select('original_file_name')->whereColumn('quote_files.id', 'quote_versions.schedule_file_id')->limit(1)
                    ]),
            ])
            ->whereNull('quotes.submitted_at');
    }

    public function toCollection($resource): DraftedCollection
    {
        return DraftedCollection::make($resource);
    }

    public function getExpiring(CarbonInterval $interval, $user = null, ?Closure $scope = null): Collection
    {
        if ($user instanceof User) {
            $user = $user->id;
        }

        throw_unless(is_null($user) || is_string($user), new \InvalidArgumentException(INV_ARG_UPK_01));

        $query = $this->expiringQuery($interval)
            ->when($user, fn($query) => $query->where('quotes.user_id', $user))
            ->with('customer');

        if ($scope instanceof Closure) {
            call_user_func($scope, $query);
        }

        return $query->get();
    }

    public function expiringQuery(CarbonInterval $interval): Builder
    {
        return $this->quote->query()
            ->drafted()
            ->whereHas(
                'customer',
                fn(Builder $query) => $query->whereNotNull('customers.valid_until')
                    ->whereDate('customers.valid_until', '>', now())
                    ->whereRaw("datediff(`customers`.`valid_until`, now()) <= ?", [$interval->d])
            );
    }

    public function rfqExist(string $rfqNumber, bool $activated = true): bool
    {
        return $this->quote->query()
            ->whereNull('submitted_at')
            ->when($activated, fn($q) => $q->whereNotNull('activated_at'))
            ->whereHas('customer', fn($q) => $q->where('rfq', $rfqNumber))
            ->exists();
    }

    public function count(array $where = []): int
    {
        return $this->quote->drafted()->where($where)->count();
    }

    public function countExpiring(CarbonInterval $interval, array $where = []): int
    {
        return $this->expiringQuery($interval)->where($where)->count();
    }

    public function find(string $id): Quote
    {
        return $this->quote->whereKey($id)->firstOrFail();
    }

    public function findVersion(string $id): QuoteVersion
    {
        return $this->quoteVersion->query()->whereId($id)->firstOrFail();
    }

    public function delete(string $id)
    {
        return $this->find($id)->delete();
    }

    public function deleteVersion(string $id): bool
    {
        return $this->findVersion($id)->delete();
    }

    public function activate(string $id)
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id)
    {
        return $this->find($id)->deactivate();
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
            new \App\Http\Query\DefaultOrderBy(column: 'quotes.updated_at', ignoreColumn: 'quotes.is_active'),
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

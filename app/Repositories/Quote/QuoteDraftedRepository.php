<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface;
use App\Http\Resources\QuoteRepository\DraftedCollection;
use App\Repositories\SearchableRepository;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection
};
use App\Models\{
    User,
    Quote\Quote,
    Quote\QuoteVersion
};
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Support\LazyCollection;

class QuoteDraftedRepository extends SearchableRepository implements QuoteDraftedRepositoryInterface
{
    /** @var \App\Models\Quote\Quote */
    protected Quote $quote;

    /** @var \App\Models\Quote\QuoteVersion */
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
        $user = auth()->user();

        return $this->quote
            ->query()
            ->when(
                /** If user is not super-admin we are retrieving the user's own quotes */
                $user->cant('view_quotes'),
                fn (Builder $query) => $query->currentUser()
                    /** Adding quotes that have been granted access to */
                    ->orWhereIn('id', $user->getPermissionTargets('quotes.read'))
                    ->orWhereIn('user_id', $user->getModulePermissionProviders('quotes.read'))
            )
            ->with(
                'versions:id,quotes.user_id,version_number,completeness,created_at,updated_at,drafted_at',
                'usingVersion.quoteFiles'
            )
            ->drafted();
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
            ->when($user, fn ($query) => $query->where('quotes.user_id', $user))
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
                fn (Builder $query) =>
                $query->whereNotNull('customers.valid_until')
                    ->whereDate('customers.valid_until', '>', now())
                    ->whereRaw("datediff(`customers`.`valid_until`, now()) <= ?", [$interval->d])
            );
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
        return $this->userQuery()->whereId($id)->firstOrFail();
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

<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface;
use App\Http\Resources\QuoteRepository\DraftedCollection;
use App\Repositories\SearchableRepository;
use App\Models\Quote\{
    Quote,
    QuoteVersion
};
use App\Models\User;
use Carbon\CarbonInterval;
use Closure;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    Collection
};

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

    public function userQuery(): Builder
    {
        return $this->quote
            ->currentUserWhen(request()->user()->cant('view_quotes'))
            ->with('versions:id,quotes.user_id,version_number,completeness,created_at,updated_at,drafted_at')
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

        $query = $this->quote->query()
            ->drafted()
            ->when(filled($user), function ($query) use ($user) {
                $query->where('quotes.user_id', $user);
            })
            ->whereHas('customer', function ($query) use ($interval) {
                $query->whereNotNull('customers.valid_until')
                    ->whereDate('customers.valid_until', '>', now())
                    ->whereRaw("datediff(`customers`.`valid_until`, now()) <= ?", [$interval->d]);
            })
            ->with('customer');

        if ($scope instanceof Closure) {
            call_user_func($scope, $query);
        }

        return $query->get();
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
}

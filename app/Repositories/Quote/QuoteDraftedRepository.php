<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface;
use App\Repositories\SearchableRepository;
use App\Builder\Pagination\Paginator;
use App\Http\Resources\QuoteRepositoryCollection;
use App\Http\Resources\QuoteRepositoryResource;
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};

class QuoteDraftedRepository extends SearchableRepository implements QuoteDraftedRepositoryInterface
{
    protected $quote;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    public function all()
    {
        return new QuoteRepositoryCollection(parent::all());
    }

    public function search(string $query = '')
    {
        return new QuoteRepositoryCollection(parent::search($query));
    }

    public function userQuery(): Builder
    {
        return $this->quote
            ->currentUserWhen(request()->user()->cant('view_quotes'))
            ->drafted()
            ->with('customer', 'company', 'user:id,email,first_name,middle_name,last_name');
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
            $this->userQuery()->with('customer', 'company')->activated(),
            $this->userQuery()->with('customer', 'company')->deactivated()
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
        return $query->currentUserWhen(request()->user()->cant('view_quotes'))
            ->drafted()
            ->with('customer', 'company', 'user:id,email,first_name,middle_name,last_name');
    }
}

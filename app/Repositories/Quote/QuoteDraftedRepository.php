<?php

namespace App\Repositories\Quote;

use App\Contracts\Repositories\Quote\QuoteDraftedRepositoryInterface;
use App\Http\Resources\QuoteRepository\QuoteDraftedRepositoryCollection;
use App\Repositories\SearchableRepository;
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\{
    Model,
    Builder
};
use Illuminate\Database\Query\Builder as DatabaseBuilder;

class QuoteDraftedRepository extends SearchableRepository implements QuoteDraftedRepositoryInterface
{
    protected $quote;

    protected $table;

    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
        $this->table = $quote->getTable();
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
            ->with('versions:id,quotes.user_id,version_number,created_at,updated_at')
            ->drafted();
    }

    public function dbQuery(): DatabaseBuilder
    {
        return $this->quote->query()
            ->currentUserWhen(request()->user()->cant('view_quotes'))
            ->toBase()
            ->whereNull("{$this->table}.submitted_at")
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

    public function toCollection($resource): QuoteDraftedRepositoryCollection
    {
        return QuoteDraftedRepositoryCollection::make($resource);
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

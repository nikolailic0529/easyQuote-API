<?php namespace App\Repositories\Quote\Margin;

use App\Builder\Pagination\Paginator;
use App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface;
use App\Repositories\SearchableRepository;
use App\Http\Requests\Margin \ {
    StoreCountryMarginRequest,
    UpdateCountryMarginRequest
};
use App\Models\Quote\Margin\CountryMargin;
use Illuminate\Database\Eloquent\Builder;

class MarginRepository extends SearchableRepository implements MarginRepositoryInterface
{
    protected $countryMargin;

    public function __construct(CountryMargin $countryMargin)
    {
        parent::__construct();
        $this->countryMargin = $countryMargin;
    }

    public function userQuery(): Builder
    {
        return $this->countryMargin->query()->userCollaboration()->with('country', 'vendor');
    }

    public function data(): array
    {
        $quote_types = __('quote.types');
        $margin_types = __('margin.types');
        $margin_methods = collect(__('margin.methods'))->diff(['Standard']);

        return compact('quote_types', 'margin_types', 'margin_methods');
    }

    public function create(StoreCountryMarginRequest $request): CountryMargin
    {
        return $request->user()->countryMargins()->create($request->validated());
    }

    public function update(UpdateCountryMarginRequest $request, string $id): CountryMargin
    {
        $countryMargin = $this->find($id);
        $countryMargin->update($request->validated());

        return $countryMargin;
    }

    public function find(string $id)
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function delete(string $id)
    {
        return $this->getCountryMargin($id)->delete();
    }

    public function all(): Paginator
    {
        $activated = $this->filterQuery($this->userQuery()->activated());
        $deactivated = $this->filterQuery($this->userQuery()->deactivated());

        return $activated->union($deactivated)->apiPaginate();
    }

    public function searchCountryMargins(string $query = ''): Paginator
    {
        $searchableFields = [
            'value^5', 'quote_type^4', 'created_at^3', 'country.name', 'vendor.name'
        ];

        $items = $this->searchOnElasticsearch($this->countryMargin, $searchableFields, $query);

        $activated = $this->buildQuery($this->countryMargin, $items, function ($query) {
            $query->userCollaboration()->with('country', 'vendor')->activated();
            $this->filterQuery($query);
        });

        $deactivated = $this->buildQuery($this->countryMargin, $items, function ($query) {
            $query->userCollaboration()->with('country', 'vendor')->deactivated();
            $this->filterQuery($query);
        });

        return $activated->union($deactivated)->apiPaginate();
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
            \App\Http\Query\OrderByCountry::class,
            \App\Http\Query\OrderByVendor::class,
            \App\Http\Query\Margin\OrderByQuoteType::class,
            \App\Http\Query\Margin\OrderByValue::class
        ];
    }
}

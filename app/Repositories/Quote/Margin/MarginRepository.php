<?php namespace App\Repositories\Quote\Margin;

use App\Builder\Pagination\Paginator;
use App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface;
use App\Http\Requests\Margin \ {
    GetPercentagesCountryMarginsRequest,
    StoreCountryMarginRequest,
    UpdateCountryMarginRequest
};
use App\Models\Quote\Margin\CountryMargin;
use Illuminate\Database\Eloquent \ {
    Builder,
    Collection
};
use Elasticsearch\Client as Elasticsearch;
use Closure, Arr;
use Illuminate\Pipeline\Pipeline;

class MarginRepository implements MarginRepositoryInterface
{
    protected $countryMargin;

    protected $search;

    public function __construct(CountryMargin $countryMargin, Elasticsearch $search)
    {
        $this->countryMargin = $countryMargin;
        $this->search = $search;
    }

    public function userCountryMarginsQuery(): Builder
    {
        $user = request()->user();
        return $user->countryMargins()->getQuery();
    }

    public function data(): array
    {
        $quote_types = __('quote.types');
        $margin_types = __('margin.types');
        $margin_methods = __('margin.methods');

        return compact('quote_types', 'margin_types', 'margin_methods');
    }

    public function percentages(GetPercentagesCountryMarginsRequest $request): CountryMargin
    {
        $user = request()->user();
        $quote = $user->quotes()->whereId($request->quote_id)->firstOrFail();

        $countryMargin = $this->userCountryMarginsQuery()
            ->quoteType($request->quote_type)->method($request->method)
            ->quoteAcceptable($quote)
            ->firstOrFail();

        return $countryMargin;
    }

    public function createCountryMargin(StoreCountryMarginRequest $request): CountryMargin
    {
        $user = request()->user();

        return $user->countryMargins()->create($request->validated());
    }

    public function updateCountryMargin(UpdateCountryMarginRequest $request): CountryMargin
    {
        $user = request()->user();

        $countryMargin = $user->countryMargins()->whereId(request('margin'))->firstOrFail();
        $countryMargin->update($request->validated());

        return $countryMargin;
    }

    public function getCountryMargin(string $id)
    {
        $user = request()->user();

        return $user->countryMargins()->whereId($id)->firstOrFail();
    }

    public function deleteCountryMargin(string $id)
    {
        $user = request()->user();

        return $user->countryMargins()->whereId($id)->firstOrFail()->delete();
    }

    public function allCountryMargins(): Paginator
    {
        $user = request()->user();

        $query = $user->countryMargins()->ordered()->getQuery();

        return $query->apiPaginate();
    }

    public function searchCountryMargins(string $query = ''): Paginator
    {
        $model = $this->countryMargin;
        $items = $this->searchOnElasticsearch($model, $query);
        $user = request()->user();

        $query = $this->buildQuery($model, $items, function ($query) use ($user) {
            $query = $query->where('user_id', $user->id)->with('country', 'vendor');
            return $this->filterQuery($query);
        });

        return $query->apiPaginate();
    }

    public function deactivateCountryMargin(string $id)
    {
        $user = request()->user();

        return $user->countryMargins()->whereId($id)->firstOrFail()->deactivate();
    }

    public function activateCountryMargin(string $id)
    {
        $user = request()->user();

        return $user->countryMargins()->whereId($id)->firstOrFail()->activate();
    }

    private function searchOnElasticsearch($model, string $query = '')
    {
        $body = [
            'query' => [
                'multi_match' => [
                    'fields' => [
                        'value^5', 'quote_type^4', 'created_at^3', 'country.name', 'vendor.name'
                    ],
                    'type' => 'phrase_prefix',
                    'query' => $query
                ]
            ]
        ];

        $items = $this->search->search([
            'index' => $model->getSearchIndex(),
            'type' => $model->getSearchType(),
            'body' => $body
        ]);

        return $items;
    }

    private function buildQuery($model, array $items, Closure $scope = null): Builder
    {
        $ids = Arr::pluck($items['hits']['hits'], '_id');

        $query = $model->query();

        if(is_callable($scope)) {
            $query = call_user_func($scope, $query) ?? $query;
        }

        return $query->whereIn("{$model->getTable()}.id", $ids);
    }

    private function filterQuery(Builder $query)
    {
        return app(Pipeline::class)
            ->send($query)
            ->through([
                \App\Http\Query\DefaultOrderBy::class,
                \App\Http\Query\Margin\JoinVendor::class,
                \App\Http\Query\Margin\JoinCountry::class,
                \App\Http\Query\Margin\OrderByCountry::class,
                \App\Http\Query\Margin\OrderByVendor::class,
                \App\Http\Query\Margin\OrderByQuoteType::class,
                \App\Http\Query\Margin\OrderByValue::class,
                \App\Http\Query\Margin\OrderByCreatedAt::class
            ])
            ->thenReturn();
    }
}

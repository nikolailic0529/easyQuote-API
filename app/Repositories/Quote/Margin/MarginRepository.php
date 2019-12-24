<?php

namespace App\Repositories\Quote\Margin;

use App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface;
use App\Repositories\SearchableRepository;
use App\Http\Requests\Margin\{
    StoreCountryMarginRequest,
    UpdateCountryMarginRequest
};
use App\Models\Quote\Margin\CountryMargin;
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    ModelNotFoundException
};
use Arr, Closure;

class MarginRepository extends SearchableRepository implements MarginRepositoryInterface
{
    protected $countryMargin;

    public function __construct(CountryMargin $countryMargin)
    {
        $this->countryMargin = $countryMargin;
    }

    public function userQuery(): Builder
    {
        return $this->countryMargin->query()->with('country', 'vendor');
    }

    public function data(): array
    {
        $quote_types = __('quote.types');
        $margin_types = __('margin.types');
        $margin_methods = collect(__('margin.methods'))->diff(['Standard']);

        return compact('quote_types', 'margin_types', 'margin_methods');
    }

    public function create($request): CountryMargin
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        abort_if(!is_array($request), 422, ARG_REQ_AR_01);

        if (!Arr::has($request, ['user_id'])) {
            abort_if(is_null(request()->user()), 422, UIDS_01);
            data_set($request, 'user_id', request()->user()->id);
        }

        return $this->countryMargin->create($request);
    }

    public function random(int $limit = 1, ?Closure $scope = null)
    {
        $method = $limit > 1 ? 'get' : 'first';

        $query = $this->countryMargin->query()->inRandomOrder()->limit($limit);

        if ($scope instanceof Closure) {
            $scope($query);
        }

        return $query->{$method}();
    }

    public function firstOrCreate(Quote $quote, array $attributes): CountryMargin
    {
        $attributes = array_merge(
            array_intersect_key($attributes, array_flip($this->countryMargin->getFillable())),
            $quote->only('vendor_id', 'country_id')
        );

        $countryMargin = $this->userQuery()->quoteAcceptable($quote)->firstOrNew($attributes);

        if ($countryMargin->isDirty()) {
            $countryMargin->user()->associate(request()->user());
            $countryMargin->save();
        }

        return $countryMargin;
    }

    public function update(UpdateCountryMarginRequest $request, string $id): CountryMargin
    {
        $countryMargin = $this->find($id);
        $countryMargin->update($request->validated());

        return $countryMargin;
    }

    public function find(string $id)
    {
        try {
            return $this->userQuery()->whereId($id)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            error_abort(MNF_01, 'MNF_01',  404);
        }
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
            \App\Http\Query\OrderByCountry::class,
            \App\Http\Query\OrderByVendor::class,
            \App\Http\Query\Margin\OrderByQuoteType::class,
            \App\Http\Query\Margin\OrderByValue::class
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
        return $this->countryMargin;
    }

    protected function searchableFields(): array
    {
        return [
            'value^5', 'quote_type^4', 'created_at^3', 'country.name', 'vendor.name'
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('country', 'vendor');
    }
}

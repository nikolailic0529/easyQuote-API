<?php namespace App\Repositories\Quote\Margin;

use App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface;
use App\Repositories\SearchableRepository;
use App\Http\Requests\Margin \ {
    StoreCountryMarginRequest,
    UpdateCountryMarginRequest
};
use App\Models\Quote\Margin\CountryMargin;
use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent \ {
    Model,
    Builder,
    ModelNotFoundException
};

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

    public function create(StoreCountryMarginRequest $request): CountryMargin
    {
        return $request->user()->countryMargins()->create($request->validated());
    }

    public function firstOrCreate(Quote $quote, array $attributes): CountryMargin
    {
        $attributes = array_merge(
            array_intersect_key($attributes, array_flip($this->countryMargin->getFillable())),
            $quote->only('vendor_id', 'country_id')
        );

        $countryMargin = $this->userQuery()->quoteAcceptable($quote)->firstOrNew($attributes);

        if($countryMargin->isDirty()) {
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
            abort(404, __('margin.404'));
        }
    }

    public function delete(string $id)
    {
        return $this->getCountryMargin($id)->delete();
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

    protected function searchableScope(Builder $query)
    {
        return $query->with('country', 'vendor');
    }
}

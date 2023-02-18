<?php

namespace App\Domain\Margin\Repositories;

use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Margin\Requests\UpdateCountryMarginRequest;
use App\Domain\Rescue\Models\BaseQuote as Quote;
use App\Domain\Shared\Eloquent\Repository\SearchableRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MarginRepository extends SearchableRepository implements \App\Domain\Margin\Contracts\MarginRepositoryInterface
{
    protected CountryMargin $countryMargin;

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

        throw_unless(is_array($request), new \InvalidArgumentException(INV_ARG_RA_01));

        return $this->countryMargin->create($request);
    }

    public function random(int $limit = 1, ?\Closure $scope = null)
    {
        $method = $limit > 1 ? 'get' : 'first';

        $query = $this->countryMargin->query()->inRandomOrder()->limit($limit);

        if ($scope instanceof \Closure) {
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

        return $this->userQuery()->quoteAcceptable($quote)->firstOrCreate($attributes);
    }

    public function update(UpdateCountryMarginRequest $request, string $id): CountryMargin
    {
        return tap($this->find($id))->update($request->validated());
    }

    public function find(string $id)
    {
        try {
            return $this->userQuery()->whereId($id)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            error_abort(MNF_01, 'MNF_01', 404);
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
            \App\Domain\Margin\Queries\Filters\OrderByCreatedAt::class,
            \App\Domain\Margin\Queries\Filters\OrderByCountry::class,
            \App\Domain\Margin\Queries\Filters\OrderByVendor::class,
            \App\Domain\Margin\Queries\Filters\OrderByQuoteType::class,
            \App\Domain\Margin\Queries\Filters\OrderByValue::class,
            \App\Foundation\Database\Eloquent\QueryFilter\DefaultOrderBy::class,
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->activated(),
            $this->userQuery()->deactivated(),
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->countryMargin;
    }

    protected function searchableFields(): array
    {
        return [
            'value^5', 'quote_type^4', 'created_at^3', 'country.name', 'vendor.name',
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('country', 'vendor');
    }
}

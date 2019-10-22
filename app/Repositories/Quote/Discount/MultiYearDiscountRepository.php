<?php namespace App\Repositories\Quote\Discount;

use App\Builder\Pagination\Paginator;
use App\Contracts\Repositories\Quote\Discount\MultiYearDiscountRepositoryInterface;
use App\Http\Requests\Discount \ {
    StoreMultiYearDiscountRequest,
    UpdateMultiYearDiscountRequest
};
use App\Models\Quote\Discount\MultiYearDiscount;
use Illuminate\Database\Eloquent\Builder;

class MultiYearDiscountRepository extends DiscountRepository implements MultiYearDiscountRepositoryInterface
{
    protected $multiYearDiscount;

    public function __construct(MultiYearDiscount $multiYearDiscount)
    {
        parent::__construct();
        $this->multiYearDiscount = $multiYearDiscount;
    }

    public function all(): Paginator
    {
        $activated = $this->filterQuery($this->userQuery()->activated());
        $deactivated = $this->filterQuery($this->userQuery()->deactivated());

        return $activated->union($deactivated)->apiPaginate();
    }

    public function search(string $query = ''): Paginator
    {
        $searchableFields = [
            'name^5', 'durations.*^4', 'created_at^3', 'country.name', 'vendor.name'
        ];

        $items = $this->searchOnElasticsearch($this->multiYearDiscount, $searchableFields, $query);

        $activated = $this->buildQuery($this->multiYearDiscount, $items, function ($query) {
            return $this->filterQuery($query->userCollaboration()->with('country', 'vendor')->activated());
        });
        $deactivated = $this->buildQuery($this->multiYearDiscount, $items, function ($query) {
            return $this->filterQuery($query->userCollaboration()->with('country', 'vendor')->deactivated());
        });

        return $activated->union($deactivated)->apiPaginate();
    }

    public function userQuery(): Builder
    {
        return $this->multiYearDiscount->query()->userCollaboration()->with('country', 'vendor');
    }

    public function find(string $id): MultiYearDiscount
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function create(StoreMultiYearDiscountRequest $request): MultiYearDiscount
    {
        return $request->user()->multiYearDiscounts()->create($request->validated());
    }

    public function update(UpdateMultiYearDiscountRequest $request, string $id): MultiYearDiscount
    {
        $discount = $this->find($id);

        $discount->update($request->validated());

        return $discount;
    }

    public function delete(string $id): bool
    {
        return $this->find($id)->delete();
    }

    public function activate(string $id): bool
    {
        return $this->find($id)->activate();
    }

    public function deactivate(string $id): bool
    {
        return $this->find($id)->deactivate();
    }

    protected function appendFilterQueryThrough(): array
    {
        return [
            \App\Http\Query\Discount\OrderByDurationsValue::class,
            \App\Http\Query\Discount\OrderByDurationsDuration::class
        ];
    }
}

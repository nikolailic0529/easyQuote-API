<?php namespace App\Repositories\Quote\Discount;

use App\Contracts\Repositories\Quote\Discount\PromotionalDiscountRepositoryInterface;
use App\Builder\Pagination\Paginator;
use App\Http\Requests\Discount \ {
    StorePromotionalDiscountRequest,
    UpdatePromotionalDiscountRequest
};
use App\Models\Quote\Discount\PromotionalDiscount;
use Illuminate\Database\Eloquent\Builder;

class PromotionalDiscountRepository extends DiscountRepository implements PromotionalDiscountRepositoryInterface
{
    protected $promotionalDiscount;

    public function __construct(PromotionalDiscount $promotionalDiscount)
    {
        parent::__construct();
        $this->promotionalDiscount = $promotionalDiscount;
    }

    public function all(): Paginator
    {
        return $this->filterQuery($this->userQuery())->apiPaginate();
    }

    public function search(string $query = ''): Paginator
    {
        $searchableFields = [
            'name^5', 'value^4', 'created_at^3', 'minimum_limit^2', 'country.name', 'vendor.name'
        ];

        $items = $this->searchOnElasticsearch($this->promotionalDiscount, $searchableFields, $query);

        $query = $this->buildQuery($this->promotionalDiscount, $items, function ($query) {
            return $this->filterQuery($query->with('country', 'vendor'));
        });

        return $query->apiPaginate();
    }

    public function userQuery(): Builder
    {
        $user = request()->user();

        return $user->promotionalDiscounts()->with('country', 'vendor')->getQuery();
    }

    public function find(string $id): PromotionalDiscount
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function create(StorePromotionalDiscountRequest $request): PromotionalDiscount
    {
        $user = request()->user();

        return $user->promotionalDiscounts()->create($request->validated());
    }

    public function update(UpdatePromotionalDiscountRequest $request, string $id): PromotionalDiscount
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
            \App\Http\Query\Discount\OrderByMinimumLimit::class,
            \App\Http\Query\Discount\OrderByValue::class,
            \App\Http\Query\DefaultGroupByActivation::class
        ];
    }
}

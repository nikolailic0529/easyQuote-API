<?php namespace App\Repositories\Quote\Discount;

use App\Contracts\Repositories\Quote\Discount\MultiYearDiscountRepositoryInterface;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Http\Requests\Discount \ {
    StoreMultiYearDiscountRequest,
    UpdateMultiYearDiscountRequest
};
use Illuminate\Database\Eloquent \ {
    Model,
    Builder
};

class MultiYearDiscountRepository extends DiscountRepository implements MultiYearDiscountRepositoryInterface
{
    protected $multiYearDiscount;

    public function __construct(MultiYearDiscount $multiYearDiscount)
    {
        $this->multiYearDiscount = $multiYearDiscount;
    }

    public function userQuery(): Builder
    {
        return $this->multiYearDiscount->query()->with('country', 'vendor');
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

    protected function filterableQuery()
    {
        return [
            $this->userQuery()->activated(),
            $this->userQuery()->deactivated()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->multiYearDiscount;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'durations.*^4', 'created_at^3', 'country.name', 'vendor.name'
        ];
    }

    protected function searchableScope(Builder $query)
    {
        return $query->with('country', 'vendor');
    }
}

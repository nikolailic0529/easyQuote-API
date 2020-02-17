<?php

namespace App\Repositories\Quote\Discount;

use App\Contracts\Repositories\Quote\Discount\PromotionalDiscountRepositoryInterface;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Http\Requests\Discount\UpdatePromotionalDiscountRequest;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    ModelNotFoundException
};

class PromotionalDiscountRepository extends DiscountRepository implements PromotionalDiscountRepositoryInterface
{
    protected PromotionalDiscount $promotionalDiscount;

    public function __construct(PromotionalDiscount $promotionalDiscount)
    {
        $this->promotionalDiscount = $promotionalDiscount;
    }

    public function userQuery(): Builder
    {
        return $this->promotionalDiscount->query()->with('country', 'vendor');
    }

    public function find(string $id): PromotionalDiscount
    {
        try {
            return $this->userQuery()->whereId($id)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            error_abort(DNF_01, 'DNF_01',  404);
        }
    }

    public function create($request): PromotionalDiscount
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        throw_unless(is_array($request), new \InvalidArgumentException(INV_ARG_RA_01));

        return $this->promotionalDiscount->create($request);
    }

    public function update(UpdatePromotionalDiscountRequest $request, string $id): PromotionalDiscount
    {
        return tap($this->find($id))->update($request->validated());
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
            \App\Http\Query\Discount\OrderByValue::class
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
        return $this->promotionalDiscount;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'value^4', 'created_at^3', 'minimum_limit^2', 'country.name', 'vendor.name'
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('country', 'vendor');
    }
}

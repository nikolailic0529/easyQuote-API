<?php

namespace App\Repositories\Quote\Discount;

use App\Contracts\Repositories\Quote\Discount\PrePayDiscountRepositoryInterface;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Http\Requests\Discount\UpdatePrePayDiscountRequest;
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    ModelNotFoundException
};

class PrePayDiscountRepository extends DiscountRepository implements PrePayDiscountRepositoryInterface
{
    protected $prePayDiscount;

    public function __construct(PrePayDiscount $prePayDiscount)
    {
        $this->prePayDiscount = $prePayDiscount;
    }

    public function userQuery(): Builder
    {
        return $this->prePayDiscount->query()->with('country', 'vendor');
    }

    public function find(string $id): PrePayDiscount
    {
        try {
            return $this->userQuery()->whereId($id)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            error_abort(DNF_01, 'DNF_01',  404);
        }
    }

    public function create($request): PrePayDiscount
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        throw_unless(is_array($request), new \InvalidArgumentException(INV_ARG_RA_01));

        return $this->prePayDiscount->create($request);
    }

    public function update(UpdatePrePayDiscountRequest $request, string $id): PrePayDiscount
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
        return $this->prePayDiscount;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'duration^4', 'value^4', 'created_at^3', 'country.name', 'vendor.name'
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('country', 'vendor');
    }
}

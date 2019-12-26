<?php

namespace App\Repositories\Quote\Discount;

use App\Contracts\Repositories\Quote\Discount\MultiYearDiscountRepositoryInterface;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Http\Requests\Discount\{
    StoreMultiYearDiscountRequest,
    UpdateMultiYearDiscountRequest
};
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    ModelNotFoundException
};
use Arr;

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
        try {
            return $this->userQuery()->whereId($id)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            error_abort(DNF_01, 'DNF_01',  404);
        }
    }

    public function create($request): MultiYearDiscount
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        abort_if(!is_array($request), 422, ARG_REQ_AR_01);

        if (!Arr::has($request, ['user_id'])) {
            abort_if(is_null(request()->user()), 422, UIDS_01);
            data_set($request, 'user_id', request()->user()->id);
        }

        return $this->multiYearDiscount->create($request);
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
            'name^5', 'duration^4', 'value^4', 'created_at^3', 'country.name', 'vendor.name'
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('country', 'vendor');
    }
}

<?php

namespace App\Domain\Discount\Repositories;

use App\Domain\Discount\Contracts\PrePayDiscountRepositoryInterface;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Requests\UpdatePrePayDiscountRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PrePayDiscountRepository extends DiscountRepository implements PrePayDiscountRepositoryInterface
{
    protected PrePayDiscount $prePayDiscount;

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
            error_abort(DNF_01, 'DNF_01', 404);
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
            \App\Domain\Discount\Queries\Filters\OrderByDurationsValue::class,
            \App\Domain\Discount\Queries\Filters\OrderByDurationsDuration::class,
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
        return $this->prePayDiscount;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'duration^4', 'value^4', 'created_at^3', 'country.name', 'vendor.name',
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('country', 'vendor');
    }
}

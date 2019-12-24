<?php

namespace App\Repositories\Quote\Discount;

use App\Contracts\Repositories\Quote\Discount\SNDrepositoryInterface;
use App\Models\Quote\Discount\SND;
use App\Http\Requests\Discount\{
    StoreSNDrequest,
    UpdateSNDrequest
};
use Illuminate\Database\Eloquent\{
    Model,
    Builder,
    ModelNotFoundException
};
use Arr;

class SNDrepository extends DiscountRepository implements SNDrepositoryInterface
{
    protected $snd;

    public function __construct(SND $snd)
    {
        $this->snd = $snd;
    }

    public function userQuery(): Builder
    {
        return $this->snd->query()->with('country', 'vendor');
    }

    public function find(string $id): SND
    {
        try {
            return $this->userQuery()->whereId($id)->firstOrFail();
        } catch (ModelNotFoundException $exception) {
            error_abort(DNF_01, 'DNF_01',  404);
        }
    }

    public function create($request): SND
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $request = $request->validated();
        }

        abort_if(!is_array($request), 422, ARG_REQ_AR_01);

        if (!Arr::has($request, ['user_id'])) {
            abort_if(is_null(request()->user()), 422, UIDS_01);
            data_set($request, 'user_id', request()->user()->id);
        }

        return $this->snd->create($request);
    }

    public function update(UpdateSNDrequest $request, string $id): SND
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
        return $this->snd;
    }

    protected function searchableFields(): array
    {
        return [
            'name^5', 'value^4', 'created_at^3', 'country.name', 'vendor.name'
        ];
    }

    protected function searchableScope($query)
    {
        return $query->with('country', 'vendor');
    }
}

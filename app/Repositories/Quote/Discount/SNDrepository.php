<?php namespace App\Repositories\Quote\Discount;

use App\Contracts\Repositories\Quote\Discount\SNDrepositoryInterface;
use App\Builder\Pagination\Paginator;
use App\Http\Requests\Discount \ {
    StoreSNDrequest,
    UpdateSNDrequest
};
use App\Models\Quote\Discount\SND;
use Illuminate\Database\Eloquent\Builder;

class SNDrepository extends DiscountRepository implements SNDrepositoryInterface
{
    protected $snd;

    public function __construct(SND $snd)
    {
        parent::__construct();
        $this->snd = $snd;
    }

    public function all(): Paginator
    {
        return $this->filterQuery($this->userQuery())->apiPaginate();
    }

    public function search(string $query = ''): Paginator
    {
        $searchableFields = [
            'name^5', 'value^4', 'created_at^3', 'country.name', 'vendor.name'
        ];

        $items = $this->searchOnElasticsearch($this->snd, $searchableFields, $query);

        $query = $this->buildQuery($this->snd, $items, function ($query) {
            return $this->filterQuery($query->with('country', 'vendor'));
        });

        return $query->apiPaginate();
    }

    public function userQuery(): Builder
    {
        $user = request()->user();

        return $user->SNDs()->with('country', 'vendor')->getQuery();
    }

    public function find(string $id): SND
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function create(StoreSNDrequest $request): SND
    {
        $user = request()->user();

        return $user->SNDs()->create($request->validated());
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
            \App\Http\Query\Discount\OrderByValue::class,
            \App\Http\Query\DefaultGroupByActivation::class
        ];
    }
}

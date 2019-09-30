<?php namespace App\Repositories\Quote\Discount;

use App\Contracts\Repositories\Quote\Discount\PrePayDiscountRepositoryInterface;
use App\Builder\Pagination\Paginator;
use App\Http\Requests\Discount \ {
    StorePrePayDiscountRequest,
    UpdatePrePayDiscountRequest
};
use App\Models\Quote\Discount\PrePayDiscount;
use Illuminate\Database\Eloquent\Builder;

class PrePayDiscountRepository extends DiscountRepository implements PrePayDiscountRepositoryInterface
{
    protected $prePayDiscount;

    public function __construct(PrePayDiscount $prePayDiscount)
    {
        parent::__construct();
        $this->prePayDiscount = $prePayDiscount;
    }

    public function all(): Paginator
    {
        return $this->filterQuery($this->userQuery())->apiPaginate();
    }

    public function search(string $query = ''): Paginator
    {
        $searchableFields = [
            'name^5', 'durations.*^4', 'created_at^3', 'country.name', 'vendor.name'
        ];

        $items = $this->searchOnElasticsearch($this->prePayDiscount, $searchableFields, $query);

        $query = $this->buildQuery($this->prePayDiscount, $items, function ($query) {
            return $this->filterQuery($query->with('country', 'vendor'));
        });

        return $query->apiPaginate();
    }

    public function userQuery(): Builder
    {
        $user = request()->user();

        return $user->prePayDiscounts()->with('country', 'vendor')->getQuery();
    }

    public function find(string $id): PrePayDiscount
    {
        return $this->userQuery()->whereId($id)->firstOrFail();
    }

    public function create(StorePrePayDiscountRequest $request): PrePayDiscount
    {
        $user = request()->user();

        return $user->prePayDiscounts()->create($request->validated());
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
}

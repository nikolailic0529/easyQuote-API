<?php

namespace App\Repositories\Customer;

use App\Contracts\Repositories\Customer\CustomerRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\CustomerRepositoryResource;
use App\Models\{
    Address,
    Contact,
    Customer\Customer,
};
use App\Repositories\Concerns\ResolvesImplicitModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\{
    Arr,
    Str,
    LazyCollection,
    Facades\DB,
};
use Closure;

class CustomerRepository implements CustomerRepositoryInterface
{
    use ResolvesImplicitModel;

    protected Customer $customer;

    protected string $listingCacheKey = 'customers-listing';

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function query(): Builder
    {
        return $this->customer->query()->latest();
    }

    public function cursor(?Closure $closure): LazyCollection
    {
        return $this->customer->on(MYSQL_UNBUFFERED)
            ->when($closure, $closure)
            ->cursor();
    }

    public function all()
    {
        return $this->query()->limit(1000)->get();
    }

    public function list()
    {
        return cache()->sear($this->listingCacheKey, fn () => $this->listingQuery()->get());
    }

    public function flushListingCache(): void
    {
        cache()->forget($this->listingCacheKey);
    }

    public function find(string $id)
    {
        return $this->customer->whereId($id)->firstOrFail();
    }

    public function findByRfq(string $rfq): Customer
    {
        return $this->customer->whereRfq($rfq)->firstOrFail();
    }

    public function random(): Customer
    {
        return $this->customer->query()->inRandomOrder()->firstOrFail();
    }

    public function create($attributes): Customer
    {
        return DB::transaction(
            fn () => tap($this->customer->make($attributes), function (Customer $customer) use ($attributes) {
                $customer->save();

                $customer->addresses()->sync(Arr::get($attributes, 'addresses', []));
                $customer->contacts()->sync(Arr::get($attributes, 'contacts', []));
                $customer->vendors()->sync(Arr::get($attributes, 'vendors', []));

                $customer->load('country', 'addresses');
            }, 3)
        );
    }

    public function update($customer, array $attributes): Customer
    {
        /** @var Customer */
        $customer = $this->resolveModel($customer);

        return DB::transaction(
            fn () => tap($customer->fill($attributes), function (Customer $customer) use ($attributes) {
                $customer->save();

                $customer->addresses()->sync(Arr::get($attributes, 'addresses', []));
                $customer->contacts()->sync(Arr::get($attributes, 'contacts', []));
                $customer->vendors()->sync(Arr::get($attributes, 'vendors', []));
            }, 3)
        );
    }

    public function delete($customer): bool
    {
        $customer = $this->resolveModel($customer);

        return $customer->delete();
    }

    public function toCollection($resource)
    {
        return CustomerRepositoryResource::collection($resource);
    }

    public function model(): string
    {
        return Customer::class;
    }

    protected function listingQuery(): Builder
    {
        return $this->query()->doesntHave('quotes')->latest()->limit(1000);
    }
}

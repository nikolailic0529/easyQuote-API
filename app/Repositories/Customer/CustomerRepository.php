<?php

namespace App\Repositories\Customer;

use App\Contracts\Repositories\Customer\CustomerRepositoryInterface;
use App\Events\RfqReceived;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\{
    CustomerRepositoryResource,
    CustomerResponseResource
};
use App\Repositories\Concerns\ResolvesImplicitModel;

class CustomerRepository implements CustomerRepositoryInterface
{
    use ResolvesImplicitModel;

    /** @var \App\Models\Customer\Customer */
    protected $customer;

    /** @var string */
    protected $draftedCacheKey = 'customers-drafted';

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function query(): Builder
    {
        return $this->customer->query()->latest();
    }

    public function all()
    {
        return $this->query()->limit(1000)->get();
    }

    public function drafted()
    {
        return cache()->sear($this->draftedCacheKey, function () {
            return $this->customer->drafted()->latest()->limit(1000)->get();
        });
    }

    public function forgetDraftedCache(): bool
    {
        return cache()->forget($this->draftedCacheKey);
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

    public function create($attributes)
    {
        if ($attributes instanceof FormRequest) {
            $attributes = $attributes->validated();
        }

        throw_unless(is_array($attributes), new \InvalidArgumentException(INV_ARG_RA_01));

        $customer = $this->customer->create($attributes);
        $customer->addresses()->createMany($attributes['addresses']);

        $customer->load('country', 'addresses');

        $customerResponse = CustomerResponseResource::make($customer);

        event(new RfqReceived($customer, request('client_name', 'service')));

        return $customerResponse;
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
}

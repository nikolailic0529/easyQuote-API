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

class CustomerRepository implements CustomerRepositoryInterface
{
    protected $customer;

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
            $customers = $this->customer->drafted()->limit(1000)->get();
            return CustomerRepositoryResource::collection($customers);
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

        event(new RfqReceived($customerResponse));

        return $customerResponse;
    }
}

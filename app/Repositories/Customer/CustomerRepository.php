<?php

namespace App\Repositories\Customer;

use App\Contracts\Repositories\Customer\CustomerRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Customer\Customer;
use Illuminate\Database\Eloquent\Builder;
use Arr;

class CustomerRepository implements CustomerRepositoryInterface
{
    protected $customer;

    protected $dateable = ['support_start_date', 'support_end_date'];

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
            return $this->customer->drafted()->limit(1000)->get();
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

        if (!is_array($attributes)) {
            return null;
        }

        $dates = collect(Arr::only($attributes, $this->dateable))
            ->transform(function ($date) {
                return now()->createFromFormat('m/d/Y', $date)->toDateTimeString();
            })->toArray();

        $attributes = array_merge($attributes, $dates);

        $customer = $this->customer->create($attributes);
        $customer->addresses()->createMany($attributes['addresses']);

        report_logger(['message' => S4_CS_01], ['customer' => $customer->toArray()]);

        return $customer->load('addresses', 'country')->withAppends();
    }
}

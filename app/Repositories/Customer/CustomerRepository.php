<?php namespace App\Repositories\Customer;

use App\Contracts\Repositories\Customer\CustomerRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Customer\Customer;
use Carbon\Carbon, Arr;

class CustomerRepository implements CustomerRepositoryInterface
{
    protected $customer;

    protected $dateable = ['quotation_valid_until', 'support_start_date', 'support_end_date'];

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function all()
    {
        return $this->customer->drafted()->get();
    }

    public function find(string $id)
    {
        return $this->customer->whereId($id)->firstOrFail();
    }

    public function create($attributes)
    {
        if ($attributes instanceof FormRequest) {
            $attributes = $attributes->validated();
        }

        if (!is_array($attributes)) {
            return null;
        }

        if (Arr::has($attributes, $this->dateable)) {
            $dates = collect(Arr::only($attributes, $this->dateable))
                ->transform(function ($date) {
                    return Carbon::createFromFormat('m/d/Y', $date);
                })->toArray();

            $attributes = array_merge($attributes, $dates);
        }

        $customer = $this->customer->create($attributes);
        $customer->addresses()->createMany($attributes['addresses']);

        return $customer->load('addresses');
    }
}

<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Customer\Customer;
use App\Rules\Concerns\IgnoresModel;
use Illuminate\Database\Eloquent\Builder;

class UniqueCustomer implements Rule
{
    use IgnoresModel;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return Customer::whereKey($value)
            ->whereHas('quotes', fn (Builder $query) =>
                $query->when($this->ignore, fn (Builder $query) =>
                    $query->where('quotes.id', '!=', $this->ignore)
                )
                    ->whereNotNull('submitted_at')
                    ->whereNotNull('activated_at')
            )
            ->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return Q_RFQE_01;
    }
}

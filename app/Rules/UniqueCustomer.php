<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Customer\Customer;
use App\Rules\Concerns\IgnoresModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
        return Customer::whereId($value)
            ->whereHas('quotes', function (Builder $query) {
                $query->when($this->ignore, fn (Builder $query) =>
                    $query->where('quotes.id', '!=', $this->ignore)
                )
                    ->whereNotNull('submitted_at')
                    ->whereNotNull('activated_at')
                    ->where('is_version', false);
            })
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

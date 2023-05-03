<?php

namespace App\Domain\Discount\Validation\Rules;

use App\Foundation\Validation\Rules\Concerns\IgnoresModel;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

final class UniqueValue implements Rule
{
    use IgnoresModel;

    public function __construct(
        protected readonly string $table
    ) {
    }

    /**
     * @param string $attribute
     */
    public function passes(mixed $attribute, mixed $value): bool
    {
        return DB::table($this->table)
            ->when($this->ignore, fn ($query) => $query->where('id', '!=', $this->ignore))
            ->where('name', request('name'))
            ->where('vendor_id', request('vendor_id'))
            ->where('country_id', request('country_id'))
            ->where('value', $value)
            ->whereNull('deleted_at')
            ->doesntExist();
    }

    public function message(): string
    {
        return __('discount.validation.value_unique');
    }
}

<?php

namespace App\Rules;

use App\Rules\Concerns\IgnoresModel;
use Illuminate\Contracts\Validation\Rule;
use DB;

class UniqueDuration implements Rule
{
    use IgnoresModel;

    /** @var string */
    protected string $table;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return DB::table($this->table)
            ->when($this->ignore, fn ($query) => $query->where('id', '!=', $this->ignore))
            ->where('vendor_id', request('vendor_id'))
            ->where('country_id', request('country_id'))
            ->where(function ($query) use ($value) {
                $query->whereJsonContains('durations', DB::raw("json_object('duration', '{$value}')"))
                    ->orWhereJsonContains('durations', DB::raw("json_object('duration', {$value})"))
                    ->orWhereJsonContains('durations->duration', DB::raw("json_object('duration', '{$value}')"))
                    ->orWhereJsonContains('durations->duration', DB::raw("json_object('duration', {$value})"));
            })
            ->whereNull('deleted_at')
            ->doesntExist();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return DE_02;
    }
}

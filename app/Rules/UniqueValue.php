<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use DB;

class UniqueValue implements Rule
{
    /** @var string */
    protected $table;

    /** @var string|null */
    protected $ignore;

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
            ->when($this->ignore, function ($query) {
                $query->where('id', '!=', $this->ignore);
            })
            ->where('name', request('name'))
            ->where('vendor_id', request('vendor_id'))
            ->where('country_id', request('country_id'))
            ->where('value', $value)
            ->whereNull('deleted_at')
            ->doesntExist();
    }

    /**
     * Sets a key to ignore in existence query.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $model
     * @return self
     */
    public function ignore($model): self
    {
        if (is_string($model)) {
            $this->ignore = $model;
        }

        if ($model instanceof Model) {
            $this->ignore = $model->getKey();
        }

        return $this;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return DE_01;
    }
}

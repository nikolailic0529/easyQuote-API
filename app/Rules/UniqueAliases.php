<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\QuoteFile\ImportableColumnAlias;
use App\Rules\Concerns\IgnoresModel;
use Illuminate\Database\Eloquent\Builder;

class UniqueAliases implements Rule
{
    use IgnoresModel;

    /** @var array */
    protected array $nonUniqueAliases = [];

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $aliases = ImportableColumnAlias::hasRegularColumn()
            ->when($this->ignore, function (Builder $query) {
                $query->where('importable_column_id', '!=', $this->ignore);
            })
            ->whereIn('alias', $value)->pluck('alias');

        if ($aliases->isEmpty()) {
            return true;
        }

        $this->nonUniqueAliases = $aliases->toArray();

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The following aliases are already defined in the system: ' . implode(', ', $this->nonUniqueAliases) . '.';
    }
}

<?php

namespace App\Domain\Margin\Models;

use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Rescue\Models\BaseQuote as Quote;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Domain\Shared\Eloquent\Contracts\HasOrderedScope;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\Vendor\Concerns\BelongsToVendor;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

abstract class Margin extends Model implements HasOrderedScope, ActivatableInterface
{
    use Uuid;
    use EloquentJoin;
    use Multitenantable;
    use BelongsToUser;
    use BelongsToVendor;
    use SoftDeletes;
    use Activatable;

    const METHODS = ['No Margin', 'Standard'];

    protected $attributes = [
        'is_fixed' => false,
        'value' => 0,
    ];

    protected $hidden = [
        'deleted_at', 'drafted_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_fixed' => 'boolean',
    ];

    public function markAsFixed()
    {
        return $this->forceFill([
            'is_fixed' => true,
        ])->save();
    }

    public function markAsPercentage()
    {
        return $this->forceFill([
            'is_fixed' => false,
        ])->save();
    }

    public function isFixed()
    {
        return $this->is_fixed;
    }

    public function isPercentage()
    {
        return !$this->is_fixed;
    }

    public function getValueAttribute()
    {
        return round((float) $this->attributes['value'], 2);
    }

    /**
     * Value Calculation.
     *
     * @param string|int|float $value
     */
    public function calculate($value): float
    {
        $value = (float) $value;

        $computedValue = $this->isPercentage()
            ? $value + ($value * ($this->diff_value / 100))
            : $value + $this->diff_value;

        return $computedValue;
    }

    public function getDiffValueAttribute()
    {
        if (Str::snake($this->method) === 'no_margin') {
            return $this->value;
        }

        return -$this->value;
    }

    public function isStandard()
    {
        return Str::snake($this->method) === 'standard';
    }

    public function isNoMargin()
    {
        return Str::snake($this->method) === 'no_margin';
    }

    public function scopeMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeQuoteType($query, string $quoteType)
    {
        return $query->where('quote_type', $quoteType);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Query Builder scope based on Quote relations.
     *
     * @param \App\Domain\Rescue\Models\Quote $quote
     */
    abstract public function scopeQuoteAcceptable(Builder $query, Quote $quote): Builder;
}

<?php namespace App\Models\Quote\Margin;

use App\Models \ {
    UuidModel,
    Quote\Quote
};
use App\Traits \ {
    BelongsToUser,
    BelongsToVendor,
    Activatable
};
use Illuminate\Database\Eloquent \ {
    Builder,
    SoftDeletes
};
use App\Contracts\HasOrderedScope;
use Carbon\Carbon, Str;

abstract class Margin extends UuidModel implements HasOrderedScope
{
    use BelongsToUser, BelongsToVendor, SoftDeletes, Activatable;

    protected $perPage = 8;

    protected $attributes = [
        'is_fixed' => false,
        'value' => 0
    ];

    protected $hidden = [
        'deleted_at', 'drafted_at'
    ];

    protected $casts = [
        'value' => 'decimal,2',
        'is_fixed' => 'boolean'
    ];

    public function markAsFixed()
    {
        return $this->forceFill([
            'is_fixed' => true
        ])->save();
    }

    public function markAsPercentage()
    {
        return $this->forceFill([
            'is_fixed' => false
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

    /**
     * Value Calculation
     *
     * @param [string|int|float] $value
     * @return float
     */
    public function calculate($value, $dateFrom = null, $dateTo = null)
    {
        $value = (float) $value;
        $priceDay = $value / 30;
        $period = 30;

        if(isset($dateFrom) && isset($dateTo)) {
            $dateFrom = Carbon::parse(str_replace('/', '.', $dateFrom));
            $dateTo = Carbon::parse(str_replace('/', '.', $dateTo));
            $period = $dateFrom->diffInDays($dateTo);
        }

        $price = $priceDay * $period;

        if($period < 1) {
            return number_format($value, 2);
        }

        if($this->isPercentage()) {
            $computedValue = $price + ($price * ($this->diff_value / 100));
        } else {
            $computedValue = $value + $this->diff_value;
        }

        if($computedValue < 0) {
            return 0;
        }

        return number_format($computedValue, 2);
    }

    public function getDiffValueAttribute()
    {
        if(Str::snake($this->method) === 'no_margin') {
            return $this->value;
        }

        return -$this->value;
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
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\Quote\Quote $quote
     * @return \Illuminate\Database\Eloquent\Builder
     */
    abstract public function scopeQuoteAcceptable(Builder $query, Quote $quote): Builder;
}

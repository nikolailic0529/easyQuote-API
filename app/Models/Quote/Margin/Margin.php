<?php namespace App\Models\Quote\Margin;

use App\Models\UuidModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits \ {
    BelongsToUser,
    BelongsToVendor
};
use Str;

abstract class Margin extends UuidModel
{
    use BelongsToUser, BelongsToVendor, SoftDeletes;

    protected $fillable = [
        'value', 'is_fixed', 'quote_type', 'method'
    ];

    protected $attributes = [
        'is_fixed' => false,
        'value' => 0
    ];

    protected $hidden = [
        'deleted_at', 'drafted_at'
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

    /**
     * Value Calculation
     *
     * @param [string|int|float] $value
     * @return float
     */
    public function calculate($value)
    {
        $value = (float) $value;

        if($this->is_fixed) {
            $computedValue = $value + $this->diff_value;
        } else {
            $computedValue = $value + ($value * ($this->diff_value / 100));
        };

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
}

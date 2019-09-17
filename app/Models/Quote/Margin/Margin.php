<?php namespace App\Models\Quote\Margin;

use App\Models\UuidModel;
use App\Traits \ {
    BelongsToUser,
    BelongsToVendor
};

abstract class Margin extends UuidModel
{
    use BelongsToUser, BelongsToVendor;

    protected $fillable = [
        'value', 'is_fixed'
    ];

    protected $attributes = [
        'is_fixed' => false
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
    public function calculate($value): float
    {
        $value = (float) $value;

        if($this->is_fixed) {
            $computedValue = $value - $this->value;
        } else {
            $computedValue = $value - ($value * ($this->value / 100));
        };

        if($computedValue < 0) {
            return 0;
        }

        return $computedValue;
    }
}

<?php

namespace App\Domain\Rescue\Models;

use App\Domain\Discount\Models\{SND};
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property Model $discountable
 */
class Discount extends Model
{
    use Uuid;

    protected $hidden = [
        'pivot', 'discountable_type', 'discountable_id',
    ];

    protected $appends = [
        'discount_type', 'duration', 'margin_percentage',
    ];

    public function discountable(): MorphTo
    {
        return $this->morphTo();
    }

    public function quotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, 'quote_discount');
    }

    public function quoteVersions(): BelongsToMany
    {
        return $this->belongsToMany(QuoteVersion::class, 'quote_version_discount', relatedPivotKey: 'quote_version_id');
    }

    public function getDiscountTypeAttribute()
    {
        return Str::after($this->getAttribute('discountable_type'), 'Discount\\');
    }

    public function getDurationAttribute()
    {
        return $this->attributes['duration'] ?? $this->pivot->duration ?? null;
    }

    public function getMarginPercentageAttribute()
    {
        $value = $this->attributes['margin_percentage'] ?? $this->pivot->margin_percentage ?? null;

        return isset($value) ? number_format($value, 2) : null;
    }

    public function getValue(float $total = 0)
    {
        $discount = $this->discountable;

        if ($discount instanceof MultiYearDiscount || $discount instanceof PrePayDiscount) {
            $durations = Collection::wrap($discount->durations);
            $percentage = (float) data_get($durations, 'duration.value');

            return $percentage;
        }

        if ($discount instanceof PromotionalDiscount) {
            $percentage = (float) $discount->value;
            $limit = $discount->minimum_limit;

            if ($limit <= $total) {
                return $percentage;
            }

            return 0;
        }

        if ($discount instanceof SND) {
            return (float) $discount->value;
        }

        return 0;
    }

    public function calculateDiscount($value, $total)
    {
        $value = (float) $value;

        return $value * $this->getValue($total) / 100;
    }

    public function scopeDiscountType($query, string $class)
    {
        return $query->whereHasMorph('discountable', $class);
    }

    public function toAttachableArray()
    {
        return [$this->id => $this->only('duration', 'margin_percentage')];
    }

    public function toDiscountableArray()
    {
        $id = $this->discountable_id;
        $duration = $this->duration;

        return compact('id', 'duration');
    }
}

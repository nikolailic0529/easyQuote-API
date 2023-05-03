<?php

namespace App\Domain\QuoteFile\Models;

use App\Domain\QuoteFile\Concerns\BelongsToQuoteFile;
use App\Domain\Rescue\Casts\SchemalessColumns;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property Collection|null $columns_data
 * @property bool|null       $is_one_pay
 * @property int|null        $page
 */
class ImportedRow extends Model
{
    use Uuid;
    use BelongsToQuoteFile;
    use SoftDeletes;

    protected $fillable = [
        'id', 'page', 'quote_file_id', 'columns_data', 'is_one_pay',
    ];

    protected $hidden = [
        'quote_file',
        'user',
        'quote_file_id',
        'created_at',
        'updated_at',
        'drafted_at',
        'deleted_at',
        'page',
        'laravel_through_key',
    ];

    protected $casts = [
        'is_selected' => 'boolean',
        'is_one_pay' => 'boolean',
        'columns_data' => SchemalessColumns::class,
    ];

    protected $attributes = [
        'is_selected' => false,
    ];

    public function markAsDrafted(): bool
    {
        return $this->forceFill(['drafted_at' => now()])->save();
    }

    public function markAsNotDrafted(): bool
    {
        return $this->forceFill(['drafted_at' => null])->save();
    }

    public function getDraftedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_time'));
    }

    public function markAsSelected(): bool
    {
        return $this->forceFill(['is_selected' => true])->save();
    }

    public function markAsUnSelected(): bool
    {
        return $this->forceFill(['is_selected' => false])->save();
    }

    public function isSelected(): bool
    {
        return (bool) $this->is_selected;
    }

    public function scopeSelected(Builder $query): Builder
    {
        return $query->where('is_selected', true);
    }

    public function scopeNotSelected(Builder $query): Builder
    {
        return $query->where('is_selected', false);
    }
}

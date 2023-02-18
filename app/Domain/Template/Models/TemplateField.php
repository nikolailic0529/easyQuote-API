<?php

namespace App\Domain\Template\Models;

use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\Rescue\Models\FieldColumn;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Concerns\{Systemable};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string|null $name
 */
class TemplateField extends Model
{
    use Uuid;
    use Systemable;

    public $timestamps = false;

    protected $table = 'template_fields';

    protected $fillable = [
        'header',
        'name',
        'is_requred',
        'is_system',
        'order',
        'template_field_type_id',
    ];

    protected $hidden = [
        'is_system',
        'user_id',
        'template_field_type_id',
        'templateFieldType',
        'pivot',
        'systemImportableColumn',
    ];

    protected $appends = [
        'type',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function templateFieldType(): BelongsTo
    {
        return $this->belongsTo(TemplateFieldType::class);
    }

    public function fieldColumn(): HasOne
    {
        return $this->hasOne(FieldColumn::class, 'template_field_id');
    }

    public function systemImportableColumn(): HasOne
    {
        return $this->hasOne(ImportableColumn::class, 'name', 'name')->system();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function getTypeAttribute()
    {
        return optional($this->templateFieldType)->name;
    }
}

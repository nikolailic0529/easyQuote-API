<?php

namespace App\Models\QuoteTemplate;

use App\Contracts\{
    ActivatableInterface,
    HasOrderedScope
};
use App\Models\{
    BaseModel,
    QuoteFile\ImportableColumn,
    Quote\FieldColumn
};
use App\Traits\{
    Activatable,
    BelongsToUser,
    BelongsToTemplateFieldType,
    Systemable,
    Search\Searchable
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class TemplateField extends BaseModel implements HasOrderedScope, ActivatableInterface
{
    use BelongsToUser,
        BelongsToTemplateFieldType,
        Systemable,
        Activatable,
        Searchable,
        SoftDeletes;

    protected $table = 'template_fields';

    protected $fillable = [
        'header',
        'name',
        'is_requred',
        'is_system',
        'is_column',
        'order',
        'default_value',
        'template_field_type_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'activated_at',
        'drafted_at',
        'is_system',
        'user_id',
        'template_field_type_id',
        'templateFieldType',
        'pivot',
        'systemImportableColumn'
    ];

    protected $appends = [
        'type'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_required' => 'boolean',
        'is_column' => 'boolean'
    ];

    public function fieldColumn()
    {
        return $this->hasOne(FieldColumn::class, 'template_field_id');
    }

    public function systemImportableColumn()
    {
        return $this->hasOne(ImportableColumn::class, 'name', 'name')->system();
    }

    public function quoteTemplates()
    {
        return $this->belongsToMany(QuoteTemplate::class);
    }

    public function userQuoteTemplates()
    {
        return $this->belongsToMany(QuoteTemplate::class)
            ->currentUser();
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function getTypeAttribute()
    {
        return optional($this->templateFieldType)->name;
    }

    public function setHeaderAttribute($value)
    {
        $this->attributes['header'] = $value;

        if (!isset($this->attributes['name'])) {
            $this->attributes['name'] = Str::slug($value, '_');
        }
    }

    public function isAttached()
    {
        return $this->quoteTemplates()->exists();
    }
}

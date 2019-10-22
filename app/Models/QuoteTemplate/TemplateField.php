<?php namespace App\Models\QuoteTemplate;

use App\Models \ {
    UuidModel,
    QuoteFile\ImportableColumn,
    Quote\FieldColumn
};
use App\Traits \ {
    Activatable,
    BelongsToUser,
    BelongsToTemplateFieldType,
    Collaboration\BelongsToCollaboration,
    Systemable
};
use App\Contracts\HasOrderedScope;
use App\Traits\Search\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class TemplateField extends UuidModel implements HasOrderedScope
{
    use BelongsToCollaboration,
        BelongsToUser,
        BelongsToTemplateFieldType,
        Systemable,
        Activatable,
        Searchable,
        SoftDeletes;

    protected $table = 'template_fields';

    protected $fillable = [
        'header', 'default_value', 'template_field_type_id'
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
        if(!isset($this->templateFieldType)) {
            return null;
        }

        return $this->templateFieldType->name;
    }

    public function setHeaderAttribute($value)
    {
        $this->attributes['header'] = $value;
        $this->attributes['name'] = Str::name($value);
    }

    public function isAttached()
    {
        return $this->quoteTemplates()->exists();
    }
}

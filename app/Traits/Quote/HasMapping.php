<?php

namespace App\Traits\Quote;

use App\Collections\MappedRows;
use App\Models\{
    Quote\FieldColumn,
    QuoteFile\ImportableColumn,
    Template\TemplateField
};
use App\Services\QuoteQueries;
use Illuminate\Database\Eloquent\{
    Builder,
    Collection as EloquentCollection,
    Relations\BelongsToMany,
    Relations\HasMany,
};
use Illuminate\Support\Collection;

/**
 * @property EloquentCollection $fieldsColumns
 */
trait HasMapping
{
    public ?MappedRows $computableRows = null;

    public ?MappedRows $renderableRows = null;

    /**
     * Template Fields which will be displayed only for S4 Service.
     *
     * @var array
     */
    protected array $systemHiddenFields = ['service_level_description', 'pricing_document', 'system_handle'];

    /**
     * Template Fields which will be hidden when Quote Mode is Contract.
     *
     * @var array
     */
    protected array $contractHiddenFields = ['price', 'searchable'];

    public function fieldsColumns(): HasMany
    {
        return $this->hasMany(FieldColumn::class)->with('templateField');
    }

    public function templateFields(): BelongsToMany
    {
        return $this->belongsToMany(TemplateField::class, 'quote_field_column', 'quote_id');
    }

    public function importableColumns(): BelongsToMany
    {
        return $this->belongsToMany(ImportableColumn::class, 'quote_field_column', 'quote_id');
    }

    public function defaultTemplateFields(): BelongsToMany
    {
        return $this->templateFields()->with('systemImportableColumn')->where('is_default_enabled', true);
    }

    public function attachColumnToField(TemplateField $templateField, $importableColumn, array $attributes = [])
    {
        $template_field_id = $templateField->id;
        $importable_column_id = optional($importableColumn)->id;
        $attributes = array_intersect_key($attributes, FieldColumn::defaultAttributesToArray());
        $attributes = array_merge($attributes, compact('importable_column_id'));

        if ($this->templateFields()->whereId($template_field_id)->exists()) {
            return $this->templateFields()->updateExistingPivot($template_field_id, $attributes);
        }

        return $this->templateFields()->attach([$template_field_id => $attributes]);
    }

    public function detachTemplateField(TemplateField $templateField)
    {
        return $this->templateFields()->detach($templateField->id);
    }

    public function detachColumnsFields()
    {
        return $this->templateFields()->detach();
    }

    public function getFieldColumnAttribute(): EloquentCollection
    {
        return (new QuoteQueries)->columnsMappingQuery($this)->get();
    }

    public function templateFieldsToArray(...$except): array
    {
        if (is_array(head($except))) {
            $except = head($except);
        }

        return $this->templateFields->whereNotIn('name', $except)->sortBy('order')->pluck('name')->toArray();
    }

    public function rowsHeaderToArray(...$except): array
    {
        if (is_array(head($except))) {
            $except = head($except);
        }

        $except = array_unique(array_merge($except, $this->hiddenFieldsToArray()));

        return $this->templateFields->whereNotIn('name', $except)
            ->sortBy('order')
            ->pluck('header', 'name')
            ->map(fn ($header, $name) => $this->modeTemplate->dataHeader($name, $header))
            ->toArray();
    }

    public function hiddenFieldsToArray(): array
    {
        return $this->fieldsColumns->where('is_preview_visible', false)->pluck('templateField.name')->toArray();
    }

    public function getHiddenFieldsAttribute(): array
    {
        return $this->hiddenFieldsToArray();
    }

    public function getSystemHiddenFieldsAttribute(): array
    {
        $systemHiddenFields = $this->systemHiddenFields;

        if ($this->isMode(QT_TYPE_CONTRACT)) {
            array_push($systemHiddenFields, ...$this->contractHiddenFields);
        }

        return $this->templateFields->whereIn('name', $systemHiddenFields)->pluck('name')->toArray();
    }

    public function getSortFieldsAttribute(): Collection
    {
        return $this->fieldsColumns->whereNotNull('sort')->map(
            fn ($column) => ['name' => $column->templateField->name, 'direction' => $column->sort]
        )->values();
    }

    public function getComputableRowsCacheKeyAttribute(): string
    {
        return "quote-computable-rows:{$this->id}";
    }

    public function getMappingReviewCacheKeyAttribute(): string
    {
        return "mapping-review-data:{$this->id}";
    }

    public function forgetCachedMappingReview(): void
    {
        cache()->forget($this->mappingReviewCacheKey);
    }
}

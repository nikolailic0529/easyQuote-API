<?php namespace App\Traits\Quote;

use App\Models \ {
    Quote\FieldColumn,
    QuoteFile\ImportableColumn,
    QuoteTemplate\TemplateField
};

trait HasMapping
{
    public function fieldsColumns()
    {
        return $this->hasMany(FieldColumn::class);
    }

    public function templateFields()
    {
        return $this->belongsToMany(TemplateField::class, 'quote_field_column', 'quote_id');
    }

    public function importableColumns()
    {
        return $this->belongsToMany(ImportableColumn::class, 'quote_field_column', 'quote_id');
    }

    public function defaultTemplateFields()
    {
        return $this->templateFields()->with('systemImportableColumn')->where('is_default_enabled', true);
    }

    public function attachColumnToField(TemplateField $templateField, $importableColumn, array $attributes = [])
    {
        $template_field_id = $templateField->id;
        $importable_column_id = $importableColumn->id ?? null;
        $attributes = array_intersect_key($attributes, (new FieldColumn)->getAttributes());
        $attributes = array_merge($attributes, compact('importable_column_id'));

        if($this->templateFields()->whereId($template_field_id)->exists()) {
            return $this->templateFields()->updateExistingPivot(
                $template_field_id, $attributes
            );
        }

        return $this->templateFields()->attach([
            $template_field_id => $attributes
        ]);
    }

    public function detachTemplateField(TemplateField $templateField)
    {
        return $this->templateFields()->detach($templateField->id);
    }

    public function detachColumnsFields()
    {
        return $this->templateFields()->detach();
    }

    public function getMappingAttribute()
    {
        $mapping = $this->fieldsColumns()
            ->with('importableColumn', 'templateField.systemImportableColumn')
            ->get()
            ->mapWithKeys(function ($fieldColumn) {
                return [$fieldColumn->templateField->name => $fieldColumn->importableColumn->id ?? $fieldColumn->templateField->systemImportableColumn->id];
            });

        return $mapping;
    }

    public function getRowsDataByColumnsAttribute($selected = false)
    {
        $fieldsColumns = $this->fieldsColumns()->with('importableColumn', 'templateField')->get();
        $importableColumns = data_get($fieldsColumns, '*.importable_column_id');

        $query = $selected ? $this->selectedRowsData() : $this->rowsData();

        return $query->with(['columnsData' => function ($query) use ($importableColumns) {
            $query->whereIn('imported_columns.importable_column_id', $importableColumns)
            ->join('quote_field_column', function ($join) {
                $join->on('imported_columns.importable_column_id', '=', 'quote_field_column.importable_column_id')
                    ->where('quote_field_column.quote_id', '=', $this->id);
            })
            ->join('template_fields', function ($join) {
                $join->on('template_fields.id', '=', 'quote_field_column.template_field_id')
                    ->select('template_fields.name as template_field_name');
            })->select(['imported_columns.*', 'template_fields.name as template_field_name']);
        }])->get();
    }

    public function getSelectedRowsDataByColumnsAttribute()
    {
        return $this->getRowsDataByColumnsAttribute(true);
    }

    public function getFieldColumnAttribute()
    {
        if(!isset($this->quoteTemplate) || !isset($this->quoteTemplate->templateFields)) {
            return [];
        }

        $quoteTemplate = $this->quoteTemplate()->with(['templateFields.fieldColumn' => function ($query) {
            return $query->where('quote_id', $this->id)->withDefault(FieldColumn::make([]));
        }])->first();

        $templateFields = $quoteTemplate->templateFields->map(function ($templateField) {
            $template_field_id = $templateField->id;
            $template_field_name = $templateField->name;
            return array_merge(compact('template_field_id', 'template_field_name'), $templateField->fieldColumn->toArray());
        });

        return $templateFields;
    }
}

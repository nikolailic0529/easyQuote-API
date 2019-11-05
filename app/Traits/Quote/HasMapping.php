<?php namespace App\Traits\Quote;

use App\Models \ {
    Quote\FieldColumn,
    QuoteFile\ImportableColumn,
    QuoteTemplate\TemplateField
};
use Illuminate\Support\Facades\DB;

trait HasMapping
{
    public function fieldsColumns()
    {
        return $this->hasMany(FieldColumn::class)->with('templateField');
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

    public function rowsDataByColumns(?array $flags = null)
    {
        $query = DB::table('imported_rows')
            ->select('imported_rows.id', 'imported_rows.is_selected')
            ->join('quote_files', 'quote_files.id', '=', 'imported_rows.quote_file_id')
            ->join('customers', function ($join) {
                $join->where('customers.id', $this->customer_id);
            });

        if(isset($flags)) {
            foreach ($flags as $flag) {
                switch ($flag) {
                    case 'default_selected':
                        $query->select('imported_rows.id', DB::raw('true as `is_selected`'));
                        break;
                    case 'where_selected':
                        $query->where('imported_rows.is_selected', true);
                        break;
                }
            }
        }

        $this->fieldsColumns->each(function ($mapping) use ($query) {
            if ($mapping->is_default_enabled) {
                switch ($mapping->templateField->name) {
                    case 'date_from':
                        $query->selectRaw(
                            "date_format(`customers`.`support_start`, '%d/%m/%Y') as {$mapping->templateField->name}"
                        );
                        break;
                    case 'date_to':
                        $query->selectRaw(
                            "date_format(`customers`.`support_end`, '%d/%m/%Y') as {$mapping->templateField->name}"
                        );
                        break;
                    case 'qty':
                        $query->selectRaw(
                            "1 as {$mapping->templateField->name}"
                        );
                        break;
                }

            } else {
                switch ($mapping->templateField->name) {
                    case 'price':
                        $query->selectRaw(
                            "max(
                                if(
                                    `imported_columns`.`name` = ?,
                                    ExtractDecimal(`imported_columns`.`value`),
                                    null
                                )
                            ) as {$mapping->templateField->name}",
                            [$mapping->templateField->name]
                        );
                        break;
                    case 'date_from':
                    case 'date_to':
                        $default = $mapping->templateField->name === 'date_from' ? 'support_start' : 'support_end';
                        $query->selectRaw(
                            "max(
                                if(
                                    `imported_columns`.`name` = ?,
                                    date_format(
                                        coalesce(
                                            if(trim(`imported_columns`.`value`) = '', `customers`.`{$default}`, null),
                                            str_to_date(`imported_columns`.`value`, '%d.%m.%Y'),
                                            str_to_date(`imported_columns`.`value`, '%d/%m/%Y'),
                                            str_to_date(`imported_columns`.`value`, '%Y.%m.%d'),
                                            str_to_date(`imported_columns`.`value`, '%Y/%m/%d'),
                                            if(`imported_columns`.`value` regexp '^[0-9]{5}$', date_add(date_add(date(if(`imported_columns`.`value` < 60, '1899-12-31', '1899-12-30')), interval floor(`imported_columns`.`value`) day), interval floor(86400*(`imported_columns`.`value`-floor(`imported_columns`.`value`))) second), null),
                                            `customers`.`{$default}`
                                        ),
                                        '%d/%m/%Y'
                                    )
                                    ,
                                    null
                                )
                            ) as {$mapping->templateField->name}",
                            [$mapping->templateField->name]
                        );
                        break;
                    default:
                        $query->selectRaw(
                            "max(if(`imported_columns`.`name` = ?, `imported_columns`.`value`, null)) as {$mapping->templateField->name}",
                            [$mapping->templateField->name]
                        );
                        break;
                }
            }
        });

        $importedColumns = DB::table('imported_columns')
            ->select('imported_row_id', 'value', 'template_fields.name')
            ->join('quote_field_column', function ($join) {
                $join->where('quote_field_column.quote_id', $this->id)
                    ->on('quote_field_column.importable_column_id', '=', 'imported_columns.importable_column_id');
            })
            ->join('template_fields', 'template_fields.id', '=', 'quote_field_column.template_field_id');

        return $query
            ->joinSub($importedColumns, 'imported_columns', function ($join) {
                $join->on('imported_columns.imported_row_id', '=', 'imported_rows.id');
            })
            ->whereNull('quote_files.deleted_at')
            ->where('quote_files.quote_id', $this->id)
            ->where('quote_files.file_type', __('quote_file.types.price'))
            ->groupBy('imported_rows.id');
    }

    public function rowsDataByColumnsCalculated(?array $flags = null)
    {
        if(!$this->calculate_list_price) {
            return $this->rowsDataByColumns($flags);
        }

        $columns = $this->templateFieldsToArray('price');

        return DB::query()
            ->fromSub($this->rowsDataByColumns($flags), 'rows_data')
            ->select(
                array_merge(
                    $columns,
                    [DB::raw("(`price` / 30 * greatest(datediff(str_to_date(`date_to`, '%d/%m/%Y'), str_to_date(`date_from`, '%d/%m/%Y')), 0)) as `price`")]
                )
            );
    }

    public function rowsDataByColumnsGroupable(string $query = '')
    {
        return $this->rowsDataByColumns(['default_selected'])
            ->join('imported_columns as groupable', function ($join) use ($query) {
                $join->on('groupable.imported_row_id', '=', 'imported_rows.id')
                    ->whereRaw('match(groupable.value) against (?)', [$query]);
            });
    }

    public function countTotalPrice()
    {
        if(!$this->templateFields->contains('name', 'price')) {
            return 0.00;
        }

        $sub = $this->rowsDataByColumnsCalculated(['where_selected']);
        $query = DB::query()->fromSub($sub, 'rows_data');

        return $query->sum('price');
    }

    public function getFieldColumnAttribute()
    {
        if(!isset($this->quoteTemplate) || !isset($this->quoteTemplate->templateFields)) {
            return [];
        }

        $this->quoteTemplate->load(['templateFields.fieldColumn' => function ($query) {
            return $query->where('quote_id', $this->id)->withDefault(FieldColumn::make([]));
        }]);

        $templateFields = $this->quoteTemplate->templateFields->map(function ($templateField) {
            $template_field_id = $templateField->id;
            $template_field_name = $templateField->name;
            return array_merge(compact('template_field_id', 'template_field_name'), $templateField->fieldColumn->toArray());
        });

        return $templateFields;
    }

    public function templateFieldsToArray(...$except)
    {
        if(is_array(head($except))) {
            $except = head($except);
        }

        return $this->templateFields->whereNotIn('name', $except)->sortBy('order')->pluck('name')->toArray();
    }

    public function rowsHeaderToArray(...$except)
    {
        if(is_array(head($except))) {
            $except = head($except);
        }

        return $this->templateFields->whereNotIn('name', $except)->sortBy('order')->pluck('header', 'name')->toArray();
    }
}

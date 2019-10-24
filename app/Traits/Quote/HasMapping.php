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

    public function rowsDataByColumns($selected = false)
    {
        $fieldsColumns = $this->fieldsColumns()->with('importableColumn', 'templateField')->get();

        DB::connection()->getPdo()->exec("
            DROP FUNCTION IF EXISTS `ExtractDecimal`;
            CREATE FUNCTION `ExtractDecimal`(in_string VARCHAR(255))
            RETURNS decimal(15,2)
            NO SQL
            BEGIN
                DECLARE ctrNumber VARCHAR(255);
                DECLARE in_string_parsed VARCHAR(255);
                DECLARE digitsAndDotsNumber VARCHAR(255) DEFAULT '';
                DECLARE finalNumber VARCHAR(255) DEFAULT '';
                DECLARE sChar VARCHAR(1);
                DECLARE inti INTEGER DEFAULT 1;
                DECLARE digitSequenceStarted boolean DEFAULT false;
                DECLARE negativeNumber boolean DEFAULT false;

                SET in_string_parsed = replace(in_string,',','.');

                IF LENGTH(in_string_parsed) > 0 THEN
                    WHILE(inti <= LENGTH(in_string_parsed)) DO
                        SET sChar = SUBSTRING(in_string_parsed, inti, 1);
                        SET ctrNumber = FIND_IN_SET(sChar, '0,1,2,3,4,5,6,7,8,9,.');
                        IF ctrNumber > 0 AND (sChar != '.' OR LENGTH(digitsAndDotsNumber) > 0) THEN
                            -- add first minus if needed
                            IF digitSequenceStarted = false AND inti > 1 AND SUBSTRING(in_string_parsed, inti-1, 1) = '-' THEN
                                SET negativeNumber = true;
                            END IF;

                            SET digitSequenceStarted = true;
                            SET digitsAndDotsNumber = CONCAT(digitsAndDotsNumber, sChar);
                        ELSEIF digitSequenceStarted = true THEN
                            SET inti = LENGTH(in_string_parsed);
                        END IF;
                        SET inti = inti + 1;
                    END WHILE;

                    SET inti = LENGTH(digitsAndDotsNumber);
                    WHILE(inti > 0) DO
                        IF(SUBSTRING(digitsAndDotsNumber, inti, 1) = '.') THEN
                            SET digitsAndDotsNumber = SUBSTRING(digitsAndDotsNumber, 1, inti-1);
                            SET inti = inti - 1;
                        ELSE
                            SET inti = 0;
                        END IF;
                    END WHILE;

                    SET inti = 1;
                    WHILE(inti <= LENGTH(digitsAndDotsNumber)-3) DO
                        SET sChar = SUBSTRING(digitsAndDotsNumber, inti, 1);
                        SET ctrNumber = FIND_IN_SET(sChar, '0,1,2,3,4,5,6,7,8,9');
                        IF ctrNumber > 0 THEN
                            SET finalNumber = CONCAT(finalNumber, sChar);
                        END IF;
                        SET inti = inti + 1;
                    END WHILE;

                    SET finalNumber = CONCAT(finalNumber, RIGHT(digitsAndDotsNumber, 3));
                    IF negativeNumber = true AND LENGTH(finalNumber) > 0 THEN
                        SET finalNumber = CONCAT('-', finalNumber);
                    END IF;

                    IF LENGTH(finalNumber) = 0 THEN
                        RETURN 0;
                    END IF;

                    RETURN CAST(finalNumber AS decimal(15,2));
                ELSE
                    RETURN 0;
                END IF;
            END
        ");

        $query = DB::table('imported_rows')
            ->select('imported_rows.id', 'imported_rows.is_selected')
            ->join('quote_files', 'quote_files.id', '=', 'imported_rows.quote_file_id')
            ->join('customers', function ($join) {
                $join->where('customers.id', $this->customer_id);
            });

        if($selected) {
            $query->where('imported_rows.is_selected', true);
        }

        $fieldsColumns->each(function ($mapping) use ($query) {
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
                                    cast(
                                        ExtractDecimal(`imported_columns`.`value`)
                                        as decimal(8,2)),
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

    public function rowsDataByColumnsCalculated($selected = false)
    {
        $columns = $this->fieldsColumns()
            ->with('importableColumn', 'templateField')
            ->whereDoesntHave('templateField', function ($query) {
                $query->whereName('price');
            })
            ->get()
            ->pluck('templateField.name')
            ->toArray();

        return DB::query()
            ->fromSub($this->rowsDataByColumns($selected), 'rows_data')
            ->select(
                array_merge(
                    $columns,
                    [DB::raw("(`price` / 30 * greatest(datediff(str_to_date(`date_to`, '%d/%m/%Y'), str_to_date(`date_from`, '%d/%m/%Y')), 0)) as `price`")]
                )
            );
    }

    public function countTotalPrice()
    {
        $sub = $this->calculate_list_price ? $this->rowsDataByColumnsCalculated(true) : $this->rowsDataByColumns(true);

        if(!$sub->exists('price')) {
            return 0.00;
        }

        return DB::query()->fromSub($sub, 'rows_data')->sum('price');
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

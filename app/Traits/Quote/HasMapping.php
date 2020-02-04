<?php

namespace App\Traits\Quote;

use App\Models\{
    Quote\FieldColumn,
    QuoteFile\ImportableColumn,
    QuoteTemplate\TemplateField
};
use DB, Arr, Cache;

trait HasMapping
{
    protected $computableRows;

    protected $renderableRows;

    protected $totalPrice;

    /**
     * Template Fields which will be displayed only for S4 Service.
     *
     * @var array
     */
    protected $systemHiddenFields = ['service_level_description'];

    /**
     * Template Fields which will be hidden when Quote Mode is Contract.
     *
     * @var array
     */
    protected $contractHiddenFields = ['price', 'searchable'];

    public function getComputableRowsAttribute()
    {
        return $this->computableRows;
    }

    public function setComputableRowsAttribute($value)
    {
        $this->computableRows = $value;
    }

    public function getRenderableRowsAttribute()
    {
        return $this->renderableRows;
    }

    public function setRenderableRowsAttribute($value)
    {
        $this->renderableRows = $value;
    }

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
        $importable_column_id = optional($importableColumn)->id;
        $attributes = array_intersect_key($attributes, FieldColumn::defaultAttributesToArray());
        $attributes = array_merge($attributes, compact('importable_column_id'));

        if ($this->templateFields()->whereId($template_field_id)->exists()) {
            return $this->templateFields()->updateExistingPivot(
                $template_field_id,
                $attributes
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

        if (isset($flags)) {
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
            if (filled($mapping->sort)) {
                $query->orderBy($mapping->templateField->name, $mapping->sort);
            }

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
                                    `imported_columns`.`importable_column_id` = ?,
                                    null
                                )
                            ) as {$mapping->templateField->name}",
                            [$mapping->importable_column_id]
                        );
                        break;
                    case 'date_from':
                    case 'date_to':
                        $default = $mapping->templateField->name === 'date_from' ? 'support_start' : 'support_end';
                        $query->selectRaw(
                            "max(
                                if(
                                    `imported_columns`.`importable_column_id` = ?,
                                    date_format(
                                        coalesce(
                                            if(length(trim(`imported_columns`.`value`)) = 0, `customers`.`{$default}`, null),
                                            str_to_date(`imported_columns`.`value`, '%d.%m.%Y'),
                                            str_to_date(`imported_columns`.`value`, '%d/%m/%Y'),
                                            str_to_date(`imported_columns`.`value`, '%m/%d/%Y'),
                                            str_to_date(`imported_columns`.`value`, '%Y.%m.%d'),
                                            str_to_date(`imported_columns`.`value`, '%Y/%m/%d'),
                                            str_to_date(`imported_columns`.`value`, '%Y-%d-%m'),
                                            if(`imported_columns`.`value` regexp '^[0-9]{5}$', date_add(date_add(date(if(`imported_columns`.`value` < 60, '1899-12-31', '1899-12-30')), interval floor(`imported_columns`.`value`) day), interval floor(86400*(`imported_columns`.`value`-floor(`imported_columns`.`value`))) second), null),
                                            `customers`.`{$default}`
                                        ),
                                        '%d/%m/%Y'
                                    )
                                    ,
                                    null
                                )
                            ) as {$mapping->templateField->name}",
                            [$mapping->importable_column_id]
                        );
                        break;
                    case 'qty':
                        $query->selectRaw(
                            "max(if(`imported_columns`.`importable_column_id` = ?,
                                    greatest(cast(`imported_columns`.`value` as unsigned), 1),
                                    null
                                )
                            ) as {$mapping->templateField->name}",
                            [$mapping->importable_column_id]
                        );
                        break;
                    default:
                        $query->selectRaw(
                            "max(if(`imported_columns`.`importable_column_id` = ?,
                                coalesce(
                                    if(length(trim(`imported_columns`.`value`)) = 0, null, `imported_columns`.`value`),
                                    'N/A'
                                ),
                                null
                                )
                            ) as {$mapping->templateField->name}",
                            [$mapping->importable_column_id]
                        );
                        break;
                }
            }
        });

        $query
            ->join('imported_columns', function ($join) {
                $join->on('imported_columns.imported_row_id', '=', 'imported_rows.id')
                    ->whereIn('importable_column_id', $this->fieldsColumns->pluck('importable_column_id')->toArray());
            })
            ->whereNull('quote_files.deleted_at')
            ->where('quote_files.quote_id', $this->id)
            ->where('quote_files.file_type', __('quote_file.types.price'))
            ->whereColumn('imported_rows.page', '>=', 'quote_files.imported_page')
            ->groupBy('imported_rows.id');

        return $query;
    }

    public function rowsDataByColumnsCalculated(?array $flags = null, bool $calculate = false)
    {
        $templateFields = $this->templateFieldsToArray();

        if (!$calculate || !in_array('price', $templateFields)) {
            return $this->rowsDataByColumns($flags);
        }

        $columns = $templateFields;
        array_unshift($columns, 'id');

        $calculatedPrice = DB::raw("(`price` / 30 * greatest(datediff(str_to_date(`date_to`, '%d/%m/%Y'), str_to_date(`date_from`, '%d/%m/%Y')), 0)) as `price`");

        if (Arr::has(array_flip($templateFields), ['price', 'date_to', 'date_from'])) {
            $columns = array_diff($columns, ['price']);
            array_push($columns, $calculatedPrice);
        };

        return DB::query()->fromSub($this->rowsDataByColumns($flags), 'rows_data')->select($columns);
    }

    public function rowsDataByColumnsGroupable(string $query = '')
    {
        return $this->rowsDataByColumns(['default_selected'])
            ->join('imported_columns as groupable', function ($join) use ($query) {
                $join->on('groupable.imported_row_id', '=', 'imported_rows.id')
                    ->whereRaw("match(`groupable`.`value`) against ('+\"{$query}\"' in boolean mode)");
            })
            ->whereNull('group_name')
            ->orderByRaw("match(`groupable`.`value`) against ('+\"{$query}\"' in boolean mode) desc");
    }

    public function getFlattenOrGroupedRows(?array $flags = null, bool $calculate = false)
    {
        if (!$this->has_group_description || !$this->use_groups) {
            return $this->rowsDataByColumnsCalculated($flags, $calculate)->get();
        }

        return $this->groupedRows(null, $calculate)->get();
    }

    public function groupedRows(?array $flags = null, bool $calculate = false, ?string $group_name = null)
    {
        $selectable = array_merge(
            ['rows_data.id', DB::raw("true as `is_selected`"), 'groups.group_name'],
            $this->templateFieldsToArray()
        );

        return DB::query()->fromSub($this->rowsDataByColumnsCalculated($flags, $calculate), 'rows_data')
            ->select($selectable)
            ->join('imported_rows as groups', function ($join) use ($flags, $group_name) {
                $join->on('groups.id', '=', 'rows_data.id')
                    ->whereNotNull('groups.group_name');

                filled($group_name) && $join->whereGroupName($group_name);
            })
            ->groupBy('rows_data.id')
            ->orderBy('groups.group_name');
    }

    public function groupedRowsMeta(?array $flags = null, bool $calculate = false, ?string $group_name = null)
    {
        $query = DB::query()->fromSub($this->groupedRows($flags, $calculate, $group_name), 'rows_data')
            ->groupBy('group_name')
            ->select('group_name')
            ->selectRaw('count(`id`) as `total_count`');

        in_array('price', $this->templateFieldsToArray()) && $query->selectRaw('cast(sum(`price`) as decimal(15,2)) as `total_price`');;

        return $query;
    }

    public function getGroupDescriptionWithMeta(?array $flags = null, bool $calculate = false, ?string $group_name = null)
    {
        $groups_meta = collect(json_decode(json_encode($this->groupedRowsMeta($flags, $calculate, $group_name)->get()), true));
        $groups_description = collect($this->group_description);

        return $groups_description->transform(function ($group) use ($groups_meta) {
            return array_merge($group, $groups_meta->firstWhere('group_name', '===', $group['name']) ?? $this->defaultGroupMeta($group['name']));
        });
    }

    public function getGroupDescriptionWithMetaAttribute()
    {
        return $this->getGroupDescriptionWithMeta();
    }

    public function defaultGroupMeta(?string $group_name = null)
    {
        return [
            'group_name' => $group_name,
            'total_count' => 0,
            'total_price' => '0.00'
        ];
    }

    public function countTotalPrice()
    {
        if (!$this->templateFields->contains('name', 'price')) {
            return 0.0;
        }

        if (!$this->has_group_description || !$this->use_groups) {
            $sub = $this->rowsDataByColumnsCalculated(['where_selected'], $this->calculate_list_price);
        } else {
            $sub = $this->groupedRows(null, $this->calculate_list_price);
        }

        $query = DB::query()->fromSub($sub, 'rows_data');

        return (float) $query->sum('price');
    }

    public function getTotalPriceAttribute(): float
    {
        if (isset($this->totalPrice)) {
            return $this->totalPrice;
        }

        return $this->totalPrice = $this->countTotalPrice();
    }

    public function setTotalPriceAttribute(float $value)
    {
        $this->totalPrice = $value;
    }

    public function getFieldColumnAttribute()
    {
        if (!isset($this->quoteTemplate) || !isset($this->quoteTemplate->templateFields)) {
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
        if (is_array(head($except))) {
            $except = head($except);
        }

        return $this->templateFields->whereNotIn('name', $except)->sortBy('order')->pluck('name')->toArray();
    }

    public function rowsHeaderToArray(...$except)
    {
        if (is_array(head($except))) {
            $except = head($except);
        }

        $except = array_unique(array_merge($except, $this->hiddenFieldsToArray()));

        return $this->templateFields->whereNotIn('name', $except)
            ->sortBy('order')->pluck('header', 'name')
            ->map(function ($header, $name) {
                return $this->modeTemplate->dataHeader($name, $header);
            })
            ->toArray();
    }

    public function hiddenFieldsToArray()
    {
        return $this->fieldsColumns->where('is_preview_visible', false)->pluck('templateField.name')->toArray();
    }

    public function getHiddenFieldsAttribute()
    {
        return $this->hiddenFieldsToArray();
    }

    public function getSystemHiddenFieldsAttribute()
    {
        $systemHiddenFields = $this->systemHiddenFields;

        if ($this->isMode(QT_TYPE_CONTRACT)) {
            array_push($systemHiddenFields, ...$this->contractHiddenFields);
        }

        return $this->templateFields->whereIn('name', $systemHiddenFields)->pluck('name')->toArray();
    }

    public function getSortFieldsAttribute()
    {
        return $this->fieldsColumns->where('sort', '!==', null)->map(function ($fieldColumn) {
            return [
                'name' => $fieldColumn->templateField->name,
                'direction' => $fieldColumn->sort
            ];
        })->values();
    }

    public function getComputableRowsCacheKeyAttribute()
    {
        return "quote-computable-rows:{$this->id}";
    }

    public function forgetCachedComputableRows()
    {
        return Cache::forget($this->computableRowsCacheKey);
    }

    public function getMappingReviewCacheKeyAttribute()
    {
        return "mapping-review-data:{$this->id}";
    }

    public function forgetCachedMappingReview()
    {
        return Cache::forget($this->mappingReviewCacheKey);
    }
}

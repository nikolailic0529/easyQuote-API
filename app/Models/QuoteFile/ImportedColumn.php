<?php

namespace App\Models\QuoteFile;

use App\Models\{
    UuidModel,
    QuoteFile\ImportableColumn
};
use App\Traits\{
    Draftable,
    BelongsToImportedRow,
    BelongsToImportableColumn,
    HasSystemScope
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Str;

class ImportedColumn extends UuidModel
{
    use BelongsToImportableColumn, BelongsToImportedRow, Draftable, SoftDeletes, HasSystemScope;

    public $timestamps = false;

    protected $fillable = [
        'value', 'header', 'importable_column_id', 'imported_row_id'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'drafted_at', 'deleted_at',
        'unknown_header', 'importableColumn', 'imported_row_id', 'template_field_name'
    ];

    public function associateImportableColumnOrCreate($importableColumn, Collection $carry)
    {
        $carryHasImportableColumn = $carry->contains(function ($column) use ($importableColumn) {
            if (!isset($importableColumn->id)) {
                return false;
            }

            return $column->importableColumn->id === $importableColumn->id;
        });

        if ($importableColumn instanceof ImportableColumn && !$carryHasImportableColumn) {
            $this->importableColumn()->associate($importableColumn);

            return $importableColumn;
        };

        $alias = $header = $this->header;
        $name = Str::columnName($header);
        $user = request()->user();

        if (!isset($this->header) || mb_strlen(trim($this->header)) === 0) {
            $alias = $header = __('parser.unknown_column_header');
            $name = Str::columnName($header);
            $importableColumn = $user->importableColumns()->where('name', $name)->firstOrCreate(compact('header', 'name'));
            $importableColumn->aliases()->create(compact('alias'));

            $this->importableColumn()->associate($importableColumn);

            return $importableColumn;
        }

        $importableColumn = $user->importableColumns()->where('name', $name)->firstOrCreate(compact('header', 'name'));
        $importableColumn->aliases()->where('alias', $name)->firstOrCreate(compact('alias'));

        $this->importableColumn()->associate($importableColumn);

        return $importableColumn;
    }

    public function getValueAttribute()
    {
        if (!isset($this->template_field_name) || $this->template_field_name !== 'price') {
            return $this->attributes['value'];
        }

        return Str::price($this->attributes['value']);
    }
}

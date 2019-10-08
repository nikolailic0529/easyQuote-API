<?php namespace App\Models\QuoteFile;

use App\Models \ {
    UuidModel,
    Quote\Quote,
    QuoteFile\ImportableColumn
};
use App\Traits \ {
    BelongsToUser,
    BelongsToQuoteFile,
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
    use BelongsToImportableColumn, BelongsToImportedRow, BelongsToUser, BelongsToQuoteFile, Draftable, SoftDeletes, HasSystemScope;

    protected $fillable = [
        'value', 'page', 'header', 'user_id', 'importable_column_id'
    ];

    protected $hidden = [
        'user', 'user_id', 'quote_file_id', 'quote_file', 'page', 'created_at', 'updated_at', 'drafted_at', 'deleted_at',
        'unknown_header', 'importableColumn', 'imported_row_id', 'template_field_name'
    ];

    public function associateImportableColumnOrCreate($importableColumn, Collection $carry)
    {
        $carryHasImportableColumn = $carry->contains(function ($column) use ($importableColumn) {
            if(!isset($importableColumn->id)) {
                return false;
            }

            return $column->importableColumn->id === $importableColumn->id;
        });

        if($importableColumn instanceof ImportableColumn && !$carryHasImportableColumn) {
            $this->importableColumn()->associate($importableColumn);

            return $importableColumn;
        };

        $alias = $header = $this->header;
        $name = Str::columnName($header);
        $user = request()->user();

        if(!isset($this->header) || mb_strlen(trim($this->header)) === 0) {
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
}

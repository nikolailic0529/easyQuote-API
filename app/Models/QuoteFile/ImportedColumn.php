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
    BelongsToImportableColumn
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class ImportedColumn extends UuidModel
{
    use BelongsToImportableColumn, BelongsToImportedRow, BelongsToUser, BelongsToQuoteFile, Draftable, SoftDeletes;

    protected $fillable = [
        'value', 'page', 'header'
    ];

    protected $hidden = [
        'user', 'user_id', 'quote_file_id', 'quote_file', 'page', 'created_at', 'updated_at', 'drafted_at', 'deleted_at',
        'unknown_header', 'importableColumn', 'imported_row_id'
    ];

    public function associateImportableColumnOrCreate($importableColumn)
    {
        if($importableColumn instanceof ImportableColumn) {
            return $this->importableColumn()->associate($importableColumn);
        };

        $user = request()->user();
        $header = trim($this->header);
        $alias = $header;
        $name = Str::snake($header);

        $importableColumn = $this->importableColumn()->create(compact('header', 'name'));
        $importableColumn->user()->associate($user);
        $importableColumn->aliases()->create(compact('alias'));

        return $this->importableColumn()->associate($importableColumn);
    }
}

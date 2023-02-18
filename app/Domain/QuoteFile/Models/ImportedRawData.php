<?php

namespace App\Domain\QuoteFile\Models;

use App\Domain\QuoteFile\Concerns\BelongsToQuoteFile;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Concerns\{BelongsToUser};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportedRawData extends Model
{
    use Uuid;
    use BelongsToUser;
    use BelongsToQuoteFile;
    use SoftDeletes;

    protected $fillable = [
        'page', 'file_path',
    ];
}

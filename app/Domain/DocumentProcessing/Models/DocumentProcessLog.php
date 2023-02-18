<?php

namespace App\Domain\DocumentProcessing\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DocumentProcessLog.
 *
 * @property string|null $driver_id
 * @property string|null $original_file_name
 * @property string|null $file_path
 * @property string|null $file_type
 * @property string|null $comment
 * @property bool|null   $is_successful
 */
class DocumentProcessLog extends Model
{
    use Uuid;

    protected $guarded = [];
}

<?php

namespace App\Models\QuoteFile;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class QuoteFileFormat
 *
 * @property string|null $extension
 */
class QuoteFileFormat extends Model
{
    use Uuid;
}

<?php

namespace App\Domain\QuoteFile\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class QuoteFileFormat.
 *
 * @property string|null $extension
 */
class QuoteFileFormat extends Model
{
    use Uuid;
}

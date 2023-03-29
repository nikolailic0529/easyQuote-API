<?php

namespace App\Domain\Language\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 * @property string $native_name
 * @property string $code
 */
class Language extends Model
{
    use Uuid;

    protected $hidden = [
        'pivot', 'native_name',
    ];
}

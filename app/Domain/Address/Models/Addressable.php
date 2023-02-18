<?php

namespace App\Domain\Address\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Addressable extends Pivot
{
    protected $table = 'addressables';
}

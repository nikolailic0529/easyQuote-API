<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Addressable extends Pivot
{
    protected $table = 'addressables';
}

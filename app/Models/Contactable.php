<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Contactable extends Pivot
{
    protected $table = 'contactables';
}

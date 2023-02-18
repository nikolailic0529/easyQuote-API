<?php

namespace App\Domain\Rescue\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class QuoteVersionPivot extends Pivot
{
    public $timestamps = false;

    protected $table = 'quote_version';
}

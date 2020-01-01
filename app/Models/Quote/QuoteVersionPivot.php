<?php

namespace App\Models\Quote;

use Illuminate\Database\Eloquent\Relations\Pivot;

class QuoteVersionPivot extends Pivot
{
    public $timestamps = false;

    protected $table = 'quote_version';
}

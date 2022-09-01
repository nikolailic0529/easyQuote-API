<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class ModelHasCompanies extends MorphPivot
{
    protected $table = 'model_has_companies';
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Staudenmeir\EloquentHasManyDeep\HasTableAlias;

class ModelHasRoles extends Pivot
{
    use HasTableAlias;

    protected $table = 'model_has_roles';
}

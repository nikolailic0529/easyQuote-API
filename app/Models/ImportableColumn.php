<?php

namespace App\Models;

use App\Models\UuidModel;
use App\Traits \ {
    HasUser,
    Draftable
};
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportableColumn extends UuidModel
{
    use HasUser, Draftable, SoftDeletes;
}

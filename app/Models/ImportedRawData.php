<?php

namespace App\Models;

use App\Models\UuidModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Draftable;

class ImportedRawData extends UuidModel
{
    use Draftable, SoftDeletes;
}

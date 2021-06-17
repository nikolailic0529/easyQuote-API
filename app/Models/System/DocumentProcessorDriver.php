<?php

namespace App\Models\System;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class DocumentProcessorDriver extends Model
{
    use Uuid;

    public $timestamps = false;

    protected $guarded = [];
}

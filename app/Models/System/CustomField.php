<?php

namespace App\Models\System;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomField extends Model
{
    use Uuid, SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }
}

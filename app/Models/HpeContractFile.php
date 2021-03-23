<?php

namespace App\Models;

use App\Traits\Auth\Multitenantable;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HpeContractFile extends Model
{
    use Uuid, SoftDeletes, Multitenantable;

    protected $fillable = [
        'user_id', 'original_file_path', 'original_file_name', 'imported_at'
    ];

    public function hpeContractData(): HasMany
    {
        return $this->hasMany(HpeContractData::class);
    }
}

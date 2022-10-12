<?php

namespace App\Models\Pipeliner;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelinerSnapshot extends Model
{
    use Uuid, HasFactory;

    protected $guarded = [];

    public function entries(): HasMany
    {
        return $this->hasMany(PipelinerSnapshotEntry::class);
    }
}

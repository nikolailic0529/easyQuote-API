<?php

namespace App\Domain\Pipeliner\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelinerSnapshot extends Model
{
    use Uuid;
    use HasFactory;

    protected $guarded = [];

    public function entries(): HasMany
    {
        return $this->hasMany(PipelinerSnapshotEntry::class);
    }
}

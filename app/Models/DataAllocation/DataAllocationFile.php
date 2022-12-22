<?php

namespace App\Models\DataAllocation;

use App\Models\Opportunity;
use App\Traits\Uuid;
use Database\Factories\DataAllocationFileFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * App\Models\DataAllocation\DataAllocationFile
 *
 * @property string $id
 * @property \DateTimeInterface|null $imported_at
 * @property string $filepath Path to file in local filesystem
 * @property string $filename Client original file name
 * @property string $extension File extension
 * @property int $size File size in bytes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection<int, Opportunity> $opportunities
 * @property-read Collection<int, DataAllocationDistribution> $allocationRecords
 */
class DataAllocationFile extends Model
{
    use Uuid, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    protected static function newFactory(): DataAllocationFileFactory
    {
        return DataAllocationFileFactory::new();
    }

    public function allocationRecords(): HasMany
    {
        return $this->hasMany(DataAllocationRecord::class, foreignKey: 'file_id', localKey: 'id')
            ->orderBy('entity_order');
    }

//    public function opportunities(): HasManyThrough
//    {
//        return $this->hasManyThrough(Opportunity::class, DataAllocationRecord::class, secondKey: 'id', secondLocalKey: 'opportunity_id');
//    }

//    public function opportunities(): BelongsToMany
//    {
//        $pivot = new DataAllocationDistribution;
//
//        return $this->belongsToMany(
//            related: Opportunity::class,
//            table: $pivot->getTable(),
//            foreignPivotKey: $pivot->file()->getForeignKeyName()
//        )
//            ->withPivot([
//                $pivot->assignedUser()->getForeignKeyName(),
//                'entity_order',
//                'is_selected',
//                $pivot->getCreatedAtColumn(),
//                $pivot->getUpdatedAtColumn(),
//            ])
//            ->orderByPivot('entity_order')
//            ->using($pivot::class)
//            ->withTrashed();
//    }
}

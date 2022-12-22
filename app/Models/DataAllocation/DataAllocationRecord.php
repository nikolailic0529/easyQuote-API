<?php

namespace App\Models\DataAllocation;

use App\Enum\DataAllocationRecordResultEnum;
use App\Models\Opportunity;
use App\Models\User;
use App\Traits\Uuid;
use Database\Factories\DataAllocationRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $entity_order
 * @property bool $is_selected
 * @property DataAllocationRecordResultEnum $result
 * @property string|null $result_reason
 * @property-read Opportunity $opportunity
 * @property-read User $assignedUser
 */
class DataAllocationRecord extends Model
{
    use Uuid, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'result' => DataAllocationRecordResultEnum::class,
        'is_selected' => 'boolean',
    ];

    protected static function newFactory(): DataAllocationRecordFactory
    {
        return DataAllocationRecordFactory::new();
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'user_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class)->withTrashed();
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(DataAllocationFile::class, foreignKey: 'file_id');
    }
}

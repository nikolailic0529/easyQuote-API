<?php

namespace App\Models\DataAllocation;

use App\Contracts\HasOwner;
use App\Enum\DataAllocationStageEnum;
use App\Enum\DistributionAlgorithmEnum;
use App\Models\BusinessDivision;
use App\Models\Company;
use App\Models\User;
use App\Traits\Uuid;
use Database\Factories\DataAllocationFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\DataAllocation\DataAllocation
 *
 * @property string $id
 * @property string|null $user_id Foreign key to users table
 * @property string|null $company_id Foreign key to companies table
 * @property string|null $business_division_id Foreign key to business_divisions table
 * @property string|null $file_id Foreign key to data_allocation_files table
 * @property \Illuminate\Support\Carbon $assignment_start_date Assignment start date
 * @property \Illuminate\Support\Carbon|null $assignment_end_date Assignment end date
 * @property DistributionAlgorithmEnum $distribution_algorithm Distribution algorithm
 * @property DataAllocationStageEnum $stage Allocation stage
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Collection|User[] $assignedUsers
 * @property-read int|null $assigned_users_count
 * @property-read BusinessDivision|null $businessDivision
 * @property-read Company|null $company
 * @property-read \App\Models\DataAllocation\DataAllocationFile|null $file
 * @property-read User|null $owner
 */
class DataAllocation extends Model implements HasOwner
{
    use Uuid, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'assignment_start_date' => 'date',
        'assignment_end_date' => 'date',
        'stage' => DataAllocationStageEnum::class,
        'distribution_algorithm' => DistributionAlgorithmEnum::class,
    ];

    protected static function newFactory(): DataAllocationFactory
    {
        return DataAllocationFactory::new();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function businessDivision(): BelongsTo
    {
        return $this->belongsTo(BusinessDivision::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, table: 'data_allocation_user')
            ->orderByPivot('entity_order');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(DataAllocationFile::class);
    }
}

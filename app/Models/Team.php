<?php

namespace App\Models;

use App\Contracts\SearchableEntity;
use App\Traits\Uuid;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Team
 *
 * @property string|null $business_division_id
 * @property string|null $team_name
 * @property float|null $monthly_goal_amount
 * @property bool|null $is_system
 *
 * @property BusinessDivision|null $businessDivision
 * @property Collection<User>|User[] $teamLeaders
 */
class Team extends Model implements SearchableEntity
{
    use Uuid, SoftDeletes, HasFactory;

    protected $guarded = [];

    protected static function newFactory(): TeamFactory
    {
        return TeamFactory::new();
    }

    public function businessDivision(): BelongsTo
    {
        return $this->belongsTo(BusinessDivision::class);
    }

    public function teamLeaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_team_leader', null, 'team_leader_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function toSearchArray(): array
    {
        return [
            'team_name' => $this->team_name,
            'monthly_goal_amount' => $this->monthly_goal_amount
        ];
    }
}

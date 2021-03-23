<?php

namespace App\Models;

use App\Contracts\SearchableEntity;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Team
 *
 * @property string|null $team_name
 * @property float|null $monthly_goal_amount
 * @property bool|null $is_system
 */
class Team extends Model implements SearchableEntity
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

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

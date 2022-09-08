<?php

namespace App\Models\System;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\System\AppEvent
 *
 * @property string $id
 * @property string $name Event name
 * @property \Illuminate\Support\Carbon $occurred_at Event occurrence timestamp
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|AppEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AppEvent newQuery()
 * @method static \Illuminate\Database\Query\Builder|AppEvent onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|AppEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder|AppEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppEvent whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppEvent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppEvent whereOccurredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|AppEvent withTrashed()
 * @method static \Illuminate\Database\Query\Builder|AppEvent withoutTrashed()
 */
class AppEvent extends Model
{
    use Uuid, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime'
    ];
}

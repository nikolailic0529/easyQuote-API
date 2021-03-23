<?php

namespace App\Models;

use App\Traits\{
    Activity\LogsActivity,
    Auth\Multitenantable,
    BelongsToUser,
    BelongsToUsers,
    HasAttachments,
    NotifiableModel,
    Uuid,
};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Quote\Quote;

/**
 * Class Task
 *
 * @property string|null $id
 * @property string|null $user_id
 * @property string|null $taskable_id
 * @property string|null $taskable_type
 * @property string|null $task_template_id
 * @property string|null $name
 * @property array|null $content
 * @property int|null $priority
 * @property Carbon|null $expiry_date
 */
class Task extends Model
{
    public const TASKABLES = [Quote::class];

    use Uuid,
        Multitenantable,
        SoftDeletes,
        LogsActivity,
        BelongsToUsers,
        BelongsToUser,
        HasAttachments,
        NotifiableModel;

    protected $fillable = [
        'name', 'content', 'priority', 'expiry_date', 'task_template_id', 'taskable_id', 'taskable_type'
    ];

    protected $casts = [
        'content' => 'array'
    ];

    protected $dates = [
        'expiry_date'
    ];

    protected static $logAttributes = [
        'name', 'priority', 'expiry_date:expiry_date_formatted'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function taskable(): MorphTo
    {
        return $this->morphTo('taskable');
    }

    public function getItemNameAttribute()
    {
        return "Task ({$this->name})";
    }

    public function getExpiryDateFormattedAttribute()
    {
        return optional($this->expiry_date)->format(config('date.format_12h'));
    }
}

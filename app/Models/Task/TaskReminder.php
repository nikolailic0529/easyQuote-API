<?php

namespace App\Models\Task;

use App\Enum\ReminderStatus;
use App\Models\User;
use App\Traits\Uuid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $pl_reference
 * @property string|null $task_id
 * @property string|null $user_id
 * @property DateTimeInterface|null $set_date
 * @property ReminderStatus|null $status
 *
 * @property-read User|null $user
 * @property-read Task|null $task
 */
class TaskReminder extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'set_date' => 'datetime',
        'status' => ReminderStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

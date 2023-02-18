<?php

namespace App\Domain\Task\Models;

use App\Domain\AppEvent\Models\AppEvent;
use App\Domain\AppEvent\Models\ModelHasEvents;
use App\Domain\Reminder\Enum\ReminderStatus;
use App\Domain\User\Models\User;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Database\Factories\TaskReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Collection\Collection;

/**
 * @property string|null                                   $pl_reference
 * @property string|null                                   $task_id
 * @property string|null                                   $user_id
 * @property \DateTimeInterface|null                       $set_date
 * @property \App\Domain\Reminder\Enum\ReminderStatus|null $status
 * @property \App\Domain\User\Models\User|null             $user
 * @property Task|null                                     $task
 * @property Collection<int, ModelHasEvents>               $events
 */
class TaskReminder extends Model
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'set_date' => 'datetime',
        'status' => ReminderStatus::class,
    ];

    protected static function newFactory(): TaskReminderFactory
    {
        return TaskReminderFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function events(): MorphToMany
    {
        $pivot = new \App\Domain\AppEvent\Models\ModelHasEvents();

        return $this->morphToMany(
            related: AppEvent::class,
            name: 'model',
            table: $pivot->getTable(),
            relatedPivotKey: $pivot->event()->getQualifiedForeignKeyName()
        )
            ->using(ModelHasEvents::class);
    }
}

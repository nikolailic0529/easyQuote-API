<?php

namespace App\Models\Task;

use App\Enum\ReminderStatus;
use App\Models\ModelHasEvents;
use App\Models\System\AppEvent;
use App\Models\User;
use App\Traits\Uuid;
use Database\Factories\TaskReminderFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Collection\Collection;

/**
 * @property string|null $pl_reference
 * @property string|null $task_id
 * @property string|null $user_id
 * @property DateTimeInterface|null $set_date
 * @property ReminderStatus|null $status
 *
 * @property-read User|null $user
 * @property-read Task|null $task
 * @property-read Collection<int, ModelHasEvents> $events
 */
class TaskReminder extends Model
{
    use Uuid, SoftDeletes, HasFactory;

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
        $pivot = new ModelHasEvents();

        return $this->morphToMany(
            related: AppEvent::class,
            name: 'model',
            table: $pivot->getTable(),
            relatedPivotKey: $pivot->event()->getQualifiedForeignKeyName()
        )
            ->using(ModelHasEvents::class);
    }
}

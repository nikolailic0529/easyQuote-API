<?php

namespace App\Domain\Appointment\Models;

use App\Domain\AppEvent\Models\AppEvent;
use App\Domain\AppEvent\Models\ModelHasEvents;
use App\Domain\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\Reminder\Enum\ReminderStatus;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Contracts\HasOwner;
use Carbon\CarbonInterval;
use Database\Factories\AppointmentReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Collection\Collection;

/**
 * @property string|null                       $user_id
 * @property string|null                       $appointment_id
 * @property int|null                          $start_date_offset
 * @property \DateTimeInterface|null           $snooze_date
 * @property ReminderStatus|null               $status
 * @property \App\Domain\User\Models\User|null $owner
 * @property Appointment|null                  $appointment
 * @property Collection<int, ModelHasEvents>   $events
 */
class AppointmentReminder extends Model implements HasOwner, ProvidesIdForHumans
{
    use Uuid;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'snooze_date' => 'datetime',
        'status' => ReminderStatus::class,
    ];

    protected static function newFactory(): AppointmentReminderFactory
    {
        return AppointmentReminderFactory::new();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'user_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function getIdForHumans(): string
    {
        $interval = CarbonInterval::seconds($this->start_date_offset)->floor()->forHumans();

        return "$interval before";
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
            ->using(\App\Domain\AppEvent\Models\ModelHasEvents::class);
    }
}

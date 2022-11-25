<?php

namespace App\Models\Appointment;

use App\Contracts\HasOwner;
use App\Contracts\ProvidesIdForHumans;
use App\Enum\ReminderStatus;
use App\Models\ModelHasEvents;
use App\Models\System\AppEvent;
use App\Models\User;
use App\Traits\Uuid;
use Carbon\CarbonInterval;
use Database\Factories\AppointmentReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Collection\Collection;

/**
 * @property string|null $user_id
 * @property string|null $appointment_id
 * @property int|null $start_date_offset
 * @property \DateTimeInterface|null $snooze_date
 * @property ReminderStatus|null $status
 *
 * @property-read User|null $owner
 * @property-read Appointment|null $appointment
 * @property-read Collection<int, ModelHasEvents> $events
 */
class AppointmentReminder extends Model implements HasOwner, ProvidesIdForHumans
{
    use Uuid, HasFactory, SoftDeletes;

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
        return $this->belongsTo(User::class, 'user_id');
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

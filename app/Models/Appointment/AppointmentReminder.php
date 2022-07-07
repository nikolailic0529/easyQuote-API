<?php

namespace App\Models\Appointment;

use App\Contracts\HasOwner;
use App\Enum\ReminderStatus;
use App\Models\User;
use App\Traits\Uuid;
use Database\Factories\AppointmentReminderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $user_id
 * @property string|null $appointment_id
 * @property int|null $start_date_offset
 * @property \DateTimeInterface|null $snooze_date
 * @property ReminderStatus|null $status
 *
 * @property-read User|null $owner
 * @property-read Appointment|null $appointment
 */
class AppointmentReminder extends Model implements HasOwner
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
}

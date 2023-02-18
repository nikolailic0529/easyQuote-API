<?php

namespace App\Domain\Appointment\Models;

use App\Domain\Appointment\Enum\InviteeResponse;
use App\Domain\Appointment\Enum\InviteeType;
use App\Domain\Contact\Models\Contact;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Database\Factories\AppointmentContactInviteeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null                                   $pl_reference
 * @property string|null                                   $email
 * @property string|null                                   $first_name
 * @property string|null                                   $last_name
 * @property \App\Domain\Appointment\Enum\InviteeType|null $invitee_type
 * @property InviteeResponse|null                          $response
 * @property Contact|null                                  $contact
 */
class AppointmentContactInvitee extends Model
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'invitee_type' => InviteeType::class,
        'response' => InviteeResponse::class,
    ];

    protected static function newFactory(): AppointmentContactInviteeFactory
    {
        return AppointmentContactInviteeFactory::new();
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}

<?php

namespace App\Domain\Appointment\Models;

use App\Domain\Appointment\Enum\AppointmentTypeEnum;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\Reminder\Enum\ReminderStatus;
use App\Domain\Rescue\Models\Quote;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Shared\Eloquent\Concerns\HasTimestamps;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Contracts\HasOwner;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null                                               $pl_reference
 * @property string|null                                               $user_id
 * @property AppointmentTypeEnum|null                                  $activity_type
 * @property string|null                                               $subject
 * @property string|null                                               $description
 * @property Carbon|null                                               $start_date
 * @property Carbon|null                                               $end_date
 * @property string|null                                               $location
 * @property int|null                                                  $flags
 * @property bool                                                      $invitees_can_edit
 * @property SalesUnit|null                                            $salesUnit
 * @property AppointmentReminder|null                                  $reminder
 * @property Collection<int, AppointmentReminder>                      $reminders
 * @property Collection<int, AppointmentReminder>                      $activeReminders
 * @property Collection<int, ModelHasAppointments>                     $modelsHaveAppointment
 * @property Collection<int, User>                                     $users
 * @property Collection<int, AppointmentUser>                          $userRelations
 * @property Collection<int, AppointmentOpportunity>                   $opportunityRelations           // Pivot relations to linked opportunity
 * @property Collection<int, AppointmentInvitedContact>                $inviteeContactRelations        // Pivot relations to linked contact invitees
 * @property Collection<int, AppointmentInvitedUser>                   $inviteeUserRelations           // Pivot relations to linked user invitees
 * @property Collection<int, AppointmentContact>                       $contactRelations               // Pivot relations to linked contacts
 * @property Collection<int, AppointmentCompany>                       $companyRelations               // Pivot relations to linked companies
 * @property Collection<int, AppointmentRescueQuote>                   $rescueQuoteRelations           // Pivot relations to linked rescue quotes
 * @property Collection<int, AppointmentWorldwideQuote>                $worldwideQuoteRelations        // Pivot relations to linked worldwide quotes
 * @property Collection<int, AppointmentAttachment>                    $attachmentRelations            // Pivot relations to linked attachments
 * @property Collection<int, Opportunity>                              $opportunities                  Relations to linked opportunities
 * @property Collection<int, Company>                                  $companies                      Relations to linked companies
 * @property Collection<int, \App\Domain\Contact\Models\Contact>       $contacts                       Relations to linked contacts
 * @property Collection<int, AppointmentContactInvitee>                $inviteesContacts               Relations to linked contact invitees
 * @property Collection<int, \App\Domain\User\Models\User>             $inviteesUsers                  Relations to linked user invitees
 * @property Collection<int, \App\Domain\Attachment\Models\Attachment> $attachments                    Relations to linked attachments
 * @property Collection<int, \App\Domain\Rescue\Models\Quote>          $quotes                         Relations to linked rescue quotes
 * @property Collection<int, WorldwideQuote>                           $worldwideQuotes                Relations to linked worldwide quotes
 * @property Collection<int, \App\Domain\Rescue\Models\Quote>          $rescueQuotesHaveAppointment    Rescue quotes have appointment
 * @property Collection<int, WorldwideQuote>                           $worldwideQuotesHaveAppointment Worldwide quotes have appointment
 * @property Collection<int, \App\Domain\Company\Models\Company>       $contactsHaveAppointment        Contacts have appointment
 * @property Collection<int, \App\Domain\Company\Models\Company>       $companiesHaveAppointment       Companies have appointment
 * @property Collection<int, Opportunity>                              $opportunitiesHaveAppointment   Opportunities have appointment
 */
class Appointment extends Model implements HasOwner, ProvidesIdForHumans
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;
    use HasTimestamps;

    const INVITEES_CAN_EDIT = 1 << 0;

    protected $guarded = [];

    protected $casts = [
        'activity_type' => AppointmentTypeEnum::class,
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'invitees_can_edit' => 'boolean',
    ];

    protected static function newFactory(): AppointmentFactory
    {
        return AppointmentFactory::new();
    }

    public function modelsHaveAppointment(): HasMany
    {
        return $this->hasMany(ModelHasAppointments::class);
    }

    public function opportunitiesHaveAppointment(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, name: 'model', table: (new ModelHasAppointments())->getTable())->using(ModelHasAppointments::class);
    }

    public function companiesHaveAppointment(): MorphToMany
    {
        return $this->morphedByMany(Company::class, name: 'model', table: (new ModelHasAppointments())->getTable())->using(ModelHasAppointments::class);
    }

    public function rescueQuotesHaveAppointment(): MorphToMany
    {
        return $this->morphedByMany(Quote::class, name: 'model', table: (new ModelHasAppointments())->getTable())->using(ModelHasAppointments::class);
    }

    public function worldwideQuotesHaveAppointment(): MorphToMany
    {
        return $this->morphedByMany(WorldwideQuote::class, name: 'model', table: (new ModelHasAppointments())->getTable())->using(ModelHasAppointments::class);
    }

    public function contactsHaveAppointment(): MorphToMany
    {
        return $this->morphedByMany(Contact::class, name: 'model', table: (new ModelHasAppointments())->getTable())->using(ModelHasAppointments::class);
    }

    public function getFlag(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }

    public function reminder(): HasOne
    {
        return $this->hasOne(AppointmentReminder::class)->latestOfMany();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(AppointmentReminder::class)->latest();
    }

    public function activeReminders(): HasMany
    {
        return $this->hasMany(AppointmentReminder::class)
            ->where('status', '<>', ReminderStatus::Dismissed)
            ->latest();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'user_id');
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(SalesUnit::class);
    }

    public function companyRelations(): HasMany
    {
        return $this->hasMany(AppointmentCompany::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    public function contactRelations(): HasMany
    {
        return $this->hasMany(AppointmentContact::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    public function userRelations(): HasMany
    {
        return $this->hasMany(AppointmentUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Domain\User\Models\User::class);
    }

    public function opportunityRelations(): HasMany
    {
        return $this->hasMany(AppointmentOpportunity::class);
    }

    public function opportunities(): BelongsToMany
    {
        return $this->belongsToMany(Opportunity::class);
    }

    public function inviteeContactRelations(): HasMany
    {
        return $this->hasMany(AppointmentInvitedContact::class);
    }

    public function inviteesContacts(): HasMany
    {
        return $this->hasMany(AppointmentContactInvitee::class);
    }

    public function inviteeUserRelations(): HasMany
    {
        return $this->hasMany(AppointmentInvitedUser::class);
    }

    public function inviteesUsers(): BelongsToMany
    {
        return $this->belongsToMany(\App\Domain\User\Models\User::class, table: 'appointment_invited_user');
    }

    public function rescueQuoteRelations(): HasMany
    {
        return $this->hasMany(AppointmentRescueQuote::class);
    }

    public function rescueQuotes(): BelongsToMany
    {
        return $this->belongsToMany(Quote::class, table: 'appointment_rescue_quote');
    }

    public function worldwideQuoteRelations(): HasMany
    {
        return $this->hasMany(AppointmentWorldwideQuote::class);
    }

    public function worldwideQuotes(): BelongsToMany
    {
        return $this->belongsToMany(WorldwideQuote::class, 'appointment_worldwide_quote', relatedPivotKey: 'quote_id');
    }

    public function attachmentRelations(): HasMany
    {
        return $this->hasMany(AppointmentAttachment::class, 'attachable_id');
    }

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Attachment::class, name: 'attachable', relatedPivotKey: 'attachment_id')->using(AppointmentAttachment::class);
    }

    protected function inviteesCanEdit(): Attribute
    {
        return Attribute::get(fn (): bool => $this->getFlag(self::INVITEES_CAN_EDIT));
    }

    public function getIdForHumans(): string
    {
        return $this->subject;
    }
}

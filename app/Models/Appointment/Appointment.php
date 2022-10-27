<?php

namespace App\Models\Appointment;

use App\Contracts\HasOwner;
use App\Contracts\ProvidesIdForHumans;
use App\Enum\AppointmentTypeEnum;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\SalesUnit;
use App\Models\User;
use App\Traits\HasTimestamps;
use App\Traits\Uuid;
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
 * @property string|null $pl_reference
 * @property string|null $user_id
 * @property AppointmentTypeEnum|null $activity_type
 * @property string|null $subject
 * @property string|null $description
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $location
 * @property int|null $flags
 *
 * @property-read bool $invitees_can_edit
 * @property-read SalesUnit|null $salesUnit
 * @property-read AppointmentReminder|null $reminder
 * @property-read Collection<int, ModelHasAppointments> $modelsHaveAppointment
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, AppointmentUser> $userRelations
 * @property-read Collection<int, AppointmentOpportunity> $opportunityRelations // Pivot relations to linked opportunity
 * @property-read Collection<int, AppointmentInvitedContact> $inviteeContactRelations // Pivot relations to linked contact invitees
 * @property-read Collection<int, AppointmentInvitedUser> $inviteeUserRelations // Pivot relations to linked user invitees
 * @property-read Collection<int, AppointmentContact> $contactRelations // Pivot relations to linked contacts
 * @property-read Collection<int, AppointmentCompany> $companyRelations // Pivot relations to linked companies
 * @property-read Collection<int, AppointmentRescueQuote> $rescueQuoteRelations // Pivot relations to linked rescue quotes
 * @property-read Collection<int, AppointmentWorldwideQuote> $worldwideQuoteRelations // Pivot relations to linked worldwide quotes
 * @property-read Collection<int, AppointmentAttachment> $attachmentRelations // Pivot relations to linked attachments
 *
 * @property-read Collection<int, Opportunity> $opportunities Relations to linked opportunities
 * @property-read Collection<int, Company> $companies Relations to linked companies
 * @property-read Collection<int, Contact> $contacts Relations to linked contacts
 * @property-read Collection<int, AppointmentContactInvitee> $inviteesContacts Relations to linked contact invitees
 * @property-read Collection<int, User> $inviteesUsers Relations to linked user invitees
 * @property-read Collection<int, Attachment> $attachments Relations to linked attachments
 * @property-read Collection<int, Quote> $quotes Relations to linked rescue quotes
 * @property-read Collection<int, WorldwideQuote> $worldwideQuotes Relations to linked worldwide quotes
 *
 * @property-read Collection<int, Quote> $rescueQuotesHaveAppointment Rescue quotes have appointment
 * @property-read Collection<int, WorldwideQuote> $worldwideQuotesHaveAppointment Worldwide quotes have appointment
 * @property-read Collection<int, Company> $contactsHaveAppointment Contacts have appointment
 * @property-read Collection<int, Company> $companiesHaveAppointment Companies have appointment
 * @property-read Collection<int, Opportunity> $opportunitiesHaveAppointment Opportunities have appointment
 */
class Appointment extends Model implements HasOwner, ProvidesIdForHumans
{
    use Uuid, SoftDeletes, HasFactory, HasTimestamps;

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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
        return $this->belongsToMany(User::class);
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
        return $this->belongsToMany(User::class, table: 'appointment_invited_user');
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
        return Attribute::get(fn(): bool => $this->getFlag(self::INVITEES_CAN_EDIT));
    }

    public function getIdForHumans(): string
    {
        return $this->subject;
    }
}

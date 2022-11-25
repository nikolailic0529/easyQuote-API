<?php

namespace App\Models\Task;

use App\Contracts\ProvidesIdForHumans;
use App\Enum\Priority;
use App\Enum\ReminderStatus;
use App\Enum\TaskTypeEnum;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ModelHasTasks;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\SalesUnit;
use App\Models\User;
use App\Traits\{Activity\LogsActivity,
    Auth\Multitenantable,
    BelongsToUser,
    BelongsToUsers,
    HasTimestamps,
    NotifiableModel,
    Uuid};
use Carbon\Carbon;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\{Collection,
    Factories\HasFactory,
    Model,
    Relations\BelongsTo,
    Relations\HasMany,
    Relations\HasOne,
    Relations\MorphToMany,
    SoftDeletes};
use function config;

/**
 * Class Task
 *
 * @property string|null $id
 * @property string|null $pl_reference
 * @property string|null $user_id
 * @property string|null $task_template_id
 * @property TaskTypeEnum|null $activity_type
 * @property string|null $name
 * @property array|null $content
 * @property Priority|null $priority
 * @property Carbon|null $expiry_date
 *
 * @property-read User|null $user
 * @property-read SalesUnit|null $salesUnit
 * @property-read TaskReminder|null $reminder
 * @property-read TaskRecurrence|null $recurrence
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Attachment> $attachments
 *
 * @property-read Collection<int, ModelHasTasks> $linkedModelRelations
 * @property-read Collection<int, Company>|Company[] $companies
 * @property-read Collection<int, Contact>|Contact[] $contacts
 * @property-read Collection<int, Opportunity>|Opportunity[] $opportunities
 * @property-read Collection<int, TaskReminder> $reminders
 * @property-read Collection<int, TaskReminder> $activeReminders
 */
class Task extends Model implements ProvidesIdForHumans
{
    public const TASKABLES = [Quote::class];

    use Uuid,
        Multitenantable,
        SoftDeletes,
        LogsActivity,
        BelongsToUsers,
        BelongsToUser,
        NotifiableModel,
        HasFactory,
        HasTimestamps;

    protected $fillable = [
        'name', 'content', 'priority', 'expiry_date', 'task_template_id', 'taskable_id', 'taskable_type',
    ];

    protected $casts = [
        'activity_type' => TaskTypeEnum::class,
        'expiry_date' => 'datetime',
        'content' => 'array',
        'priority' => Priority::class,
    ];

    protected static $logAttributes = [
        'name', 'priority', 'expiry_date:expiry_date_formatted',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }

    public function linkedModelRelations(): HasMany
    {
        return $this->hasMany(ModelHasTasks::class);
    }

    public function opportunities(): MorphToMany
    {
        return $this->morphedByMany(Opportunity::class, name: 'model', table: (new ModelHasTasks())->getTable())->using(ModelHasTasks::class);
    }

    public function companies(): MorphToMany
    {
        return $this->morphedByMany(Company::class, name: 'model', table: (new ModelHasTasks())->getTable())->using(ModelHasTasks::class);
    }

    public function contacts(): MorphToMany
    {
        return $this->morphedByMany(Contact::class, name: 'model', table: (new ModelHasTasks())->getTable())->using(ModelHasTasks::class);
    }

    public function rescueQuotes(): MorphToMany
    {
        return $this->morphedByMany(Quote::class, name: 'model', table: (new ModelHasTasks())->getTable())->using(ModelHasTasks::class);
    }

    public function worldwideQuotes(): MorphToMany
    {
        return $this->morphedByMany(WorldwideQuote::class, name: 'model', table: (new ModelHasTasks())->getTable())->using(ModelHasTasks::class);
    }

    public function reminder(): HasOne
    {
        return $this->hasOne(TaskReminder::class)->latestOfMany();
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class)->latest();
    }

    public function activeReminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class)->where('status', '<>', ReminderStatus::Dismissed)->latest();
    }

    public function recurrence(): HasOne
    {
        return $this->hasOne(TaskRecurrence::class)->latestOfMany();
    }

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Attachment::class, name: 'attachable');
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(SalesUnit::class);
    }

    public function getItemNameAttribute(): string
    {
        return "Task ($this->name)";
    }

    public function getExpiryDateFormattedAttribute()
    {
        return $this->expiry_date?->format(config('date.format_12h'));
    }

    public function getIdForHumans(): string
    {
        return $this->name;
    }
}

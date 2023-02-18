<?php

namespace App\Domain\Task\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Notification\Concerns\NotifiableModel;
use App\Domain\Priority\Enum\Priority;
use App\Domain\Reminder\Enum\ReminderStatus;
use App\Domain\Rescue\Models\Quote;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Task\Enum\TaskTypeEnum;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\User\Concerns\BelongsToUsers;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Shared\Eloquent\Concerns\HasTimestamps;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Eloquent\Contracts\ProvidesIdForHumans;
use Carbon\Carbon;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Task.
 *
 * @property string|null                                                             $id
 * @property string|null                                                             $pl_reference
 * @property string|null                                                             $user_id
 * @property string|null                                                             $task_template_id
 * @property TaskTypeEnum|null                                                       $activity_type
 * @property string|null                                                             $name
 * @property array|null                                                              $content
 * @property \App\Domain\Priority\Enum\Priority|null                                 $priority
 * @property Carbon|null                                                             $expiry_date
 * @property \App\Domain\User\Models\User|null                                       $user
 * @property \App\Domain\SalesUnit\Models\SalesUnit|null                             $salesUnit
 * @property TaskReminder|null                                                       $reminder
 * @property TaskRecurrence|null                                                     $recurrence
 * @property Collection<int, User>                                                   $users
 * @property Collection<int, \App\Domain\Attachment\Models\Attachment>               $attachments
 * @property Collection<int, ModelHasTasks>                                          $linkedModelRelations
 * @property Collection<int, Company>|Company[]                                      $companies
 * @property Collection<int, Contact>|Contact[]                                      $contacts
 * @property Collection<int, Opportunity>|\App\Domain\Worldwide\Models\Opportunity[] $opportunities
 * @property Collection<int, TaskReminder>                                           $reminders
 * @property Collection<int, TaskReminder>                                           $activeReminders
 */
class Task extends Model implements ProvidesIdForHumans
{
    use Uuid;

    use Multitenantable;

    use SoftDeletes;

    use LogsActivity;

    use BelongsToUsers;

    use BelongsToUser;

    use NotifiableModel;

    use HasFactory;

    use HasTimestamps;
    public const TASKABLES = [Quote::class];

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
        return $this->expiry_date?->format(\config('date.format_12h'));
    }

    public function getIdForHumans(): string
    {
        return $this->name;
    }
}

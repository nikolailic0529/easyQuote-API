<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Date\Models\DateMonth;
use App\Domain\Date\Models\DateWeek;
use App\Domain\Pipeline\Models\PipelineStage;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Contracts\HasOwner;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null                                       $task_id
 * @property string|null                                       $user_id
 * @property int|null                                          $day_of_week       The bitwise mask. The setting is applicable only for RecurrenceType.Weekly, RecurrenceType.MonthlyAbsolute, RecurrenceType.YearlyAbsolute.
 * @property string|null                                       $type_id           The type of recurrence.
 * @property string|null                                       $date_day_id       The day of month.
 * @property string|null                                       $date_week_id      The week of month. This setting is applicable only for RecurrenceType.MonthlyAbsolute and RecurrenceType.YearlyAbsolute.
 * @property string|null                                       $date_month_id     The number of month this setting is applicable only for RecurrenceType.YearlyAbsolute and RecurrenceType.YearlyRelative
 * @property int|null                                          $occur_every       The number of units of a given recurrence type between occurrences according to RecurrenceTypeEnum.
 * @property int|null                                          $occurrences_count Whole natural number. The number of remaining occurrences. Each time a new one is created, it decreases. When item is set to 1, only last one can be created. If it is set to 0, recurrence is stopped. If -1, no limits are applied.
 * @property int|null                                          $condition         Bit Flag field, with limited combination of integer values.  Won = 1 (0b01),  Lost = 2 (0b10).
 * @property \DateTimeInterface|null                           $start_date        The effective start date of recurrence, which means each next recurrence must be on or after this date.
 * @property \DateTimeInterface|null                           $end_date
 * @property \App\Domain\User\Models\User|null                 $owner
 * @property \App\Domain\Date\Models\DateDay|null              $day
 * @property DateWeek|null                                     $week
 * @property DateMonth|null                                    $month
 * @property \App\Domain\Recurrence\Models\RecurrenceType|null $type
 * @property \App\Domain\Worldwide\Models\Opportunity|null     $opportunity
 * @property PipelineStage|null                                $stage
 */
class OpportunityRecurrence extends Model implements HasOwner
{
    use Uuid;
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected $guarded = [];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Recurrence\Models\RecurrenceType::class);
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Date\Models\DateDay::class, 'date_day_id');
    }

    public function week(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Date\Models\DateWeek::class, 'date_week_id');
    }

    public function month(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Date\Models\DateMonth::class, 'date_month_id');
    }
}

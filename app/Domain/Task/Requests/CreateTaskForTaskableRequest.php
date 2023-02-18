<?php

namespace App\Domain\Task\Requests;

use App\Domain\Attachment\Models\Attachment;
use App\Domain\Date\Enum\DateDayEnum;
use App\Domain\Date\Enum\DateMonthEnum;
use App\Domain\Date\Enum\DateWeekEnum;
use App\Domain\Priority\Enum\Priority;
use App\Domain\Recurrence\Enum\RecurrenceTypeEnum;
use App\Domain\Reminder\Enum\ReminderStatus;
use App\Domain\SalesUnit\Models\{SalesUnit};
use App\Domain\Task\DataTransferObjects\CreateTaskData;
use App\Domain\Task\DataTransferObjects\CreateTaskRecurrenceData;
use App\Domain\Task\DataTransferObjects\SetTaskReminderData;
use App\Domain\Task\Enum\ModelTypeHasTaskEnum;
use App\Domain\Task\Enum\TaskTypeEnum;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CreateTaskForTaskableRequest extends FormRequest
{
    protected ?CreateTaskData $createTaskData = null;
    protected ?Model $taskable = null;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'activity_type' => ['bail', 'required', new Enum(TaskTypeEnum::class)],
            'name' => ['required', 'string', 'filled', 'max:191'],
            'content' => ['present', 'array'],
            'expiry_date' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'priority' => ['required', 'integer', new Enum(Priority::class)],
            'users' => ['nullable', 'array'],
            'users.*' => ['present', 'uuid', 'distinct', Rule::exists(User::class, 'id')->withoutTrashed()],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['present', 'uuid', 'distinct', Rule::exists(Attachment::class, 'id')->withoutTrashed()],

            'reminder.set_date' => ['bail', 'required_with:reminder', 'date'],
            'reminder.status' => ['bail', 'required_with:reminder', new Enum(ReminderStatus::class)],

            'recurrence.type' => ['bail', 'required_with:recurrence', new Enum(RecurrenceTypeEnum::class)],
            'recurrence.occur_every' => ['bail', 'required_with:recurrence', 'integer', 'min:1', 'max:99'],
            'recurrence.occurrences_count' => ['bail', 'required_with:recurrence', 'integer', 'min:-1', 'max:9999'],
            'recurrence.start_date' => ['bail', 'required_with:recurrence', 'date'],
            'recurrence.end_date' => ['bail', 'nullable', 'date', 'after:recurrence.start_date'],

            'recurrence.day' => ['bail', 'required_with:recurrence', new Enum(DateDayEnum::class)],
            'recurrence.month' => ['bail', 'required_with:recurrence', new Enum(DateMonthEnum::class)],
            'recurrence.week' => ['bail', 'required_with:recurrence', new Enum(DateWeekEnum::class)],

            'recurrence.day_of_week' => ['bail', 'required_with:recurrence', 'integer', 'min:1', 'max:127'],

            'taskable.id' => ['bail', 'required', 'uuid', function (string $attr, mixed $value, \Closure $fail): void {
                if ($this->missing('taskable.type')) {
                    return;
                }

                $type = $this->input('taskable.type');

                try {
                    $this->getTaskable();
                } catch (ModelNotFoundException) {
                    $fail("Linked model of type `$type` doesnt exist with `$value` id.");
                }
            }],
            'taskable.type' => ['bail', 'required', 'string', new Enum(ModelTypeHasTaskEnum::class)],

            'sales_unit_id' => ['bail', 'required', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed()],
        ];
    }

    private function resolveTaskableModel(): ?Model
    {
        if ($this->missing('taskable.type')) {
            return null;
        }

        return new (ModelTypeHasTaskEnum::from($this->input('taskable.type'))->modelClass());
    }

    public function getTaskable(): Model
    {
        return $this->taskable ??= $this->resolveTaskableModel()
            ->newQuery()
            ->findOrFail($this->input('taskable.id'));
    }

    public function getCreateTaskData(): CreateTaskData
    {
        return $this->createTaskData ??= with(true, function (): CreateTaskData {
            /** @var \App\Domain\User\Models\User $user */
            $user = $this->user();
            $timezone = $user->timezone->utc ?? config('app.timezone');

            return new CreateTaskData([
                'sales_unit_id' => $this->input('sales_unit_id'),
                'activity_type' => TaskTypeEnum::from($this->input('activity_type')),
                'name' => $this->input('name'),
                'content' => $this->input('content'),
                'expiry_date' => transform($this->input('expiry_date'), function (string $expiryDate) use ($timezone) {
                    return Carbon::createFromFormat('Y-m-d H:i:s', $expiryDate, $timezone)->tz(config('app.timezone'));
                }),
                'priority' => Priority::from((int) $this->input('priority')),
                'users' => $this->input('users') ?? [],
                'attachments' => $this->input('attachments') ?? [],
                'reminder' => $this->whenHas('reminder', function () use ($timezone): SetTaskReminderData {
                    return SetTaskReminderData::from([
                        'set_date' => $this->date('reminder.set_date', tz: $timezone)
                            ->tz(config('app.timezone'))
                            ->toDateTimeImmutable(),

                        'status' => $this->input('reminder.status'),
                    ]);
                }, fn () => null),
                'recurrence' => $this->whenHas('recurrence', function () use ($timezone): CreateTaskRecurrenceData {
                    return new CreateTaskRecurrenceData([
                        'type' => RecurrenceTypeEnum::tryFrom($this->input('recurrence.type')),
                        'occur_every' => $this->input('recurrence.occur_every'),
                        'occurrences_count' => $this->input('recurrence.occurrences_count'),
                        'start_date' => $this->date('recurrence.start_date', tz: $timezone)
                            ->tz(config('app.timezone'))
                            ->toDateTimeImmutable(),
                        'end_date' => $this->date('recurrence.end_date', tz: $timezone)
                            ?->tz(config('app.timezone'))
                            ?->toDateTimeImmutable(),
                        'day' => DateDayEnum::tryFrom($this->input('recurrence.day')),
                        'month' => DateMonthEnum::tryFrom($this->input('recurrence.month')),
                        'week' => DateWeekEnum::tryFrom($this->input('recurrence.week')),
                        'day_of_week' => (int) $this->input('recurrence.day_of_week'),
                    ]);
                }, fn () => null),
            ]);
        });
    }
}

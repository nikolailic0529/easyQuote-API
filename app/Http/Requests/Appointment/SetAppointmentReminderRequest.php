<?php

namespace App\Http\Requests\Appointment;

use App\DTO\Appointment\SetAppointmentReminderData;
use App\Enum\ReminderStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class SetAppointmentReminderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'start_date_offset' => ['integer', 'min:0', 'max:'.(14 * 24 * 60 * 60)],
            'snooze_date' => [
                Rule::when(
                    condition: static fn(Fluent $data): bool => $data->get('status') === ReminderStatus::Snoozed->value,
                    rules: ['required'],
                    defaultRules: ['date_format:Y-m-d H:i:s']
                ),
            ],
            'status' => [new Enum(ReminderStatus::class)],
        ];
    }

    public function getData(): SetAppointmentReminderData
    {
        /** @var User $user */
        $user = $this->user();

        $input = $this->input();

        $timezone = $user->timezone->utc ?? config('app.timezone');

        if (isset($input['snooze_date'])) {
            $input['snooze_date'] = $this->date('snooze_date', tz: $timezone)
                ->tz(config('app.timezone'))
                ->toDateTimeImmutable();
        }

        return SetAppointmentReminderData::from($input);
    }
}
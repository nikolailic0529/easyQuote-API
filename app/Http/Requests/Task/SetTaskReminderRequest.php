<?php

namespace App\Http\Requests\Task;

use App\DTO\Tasks\SetTaskReminderData;
use App\Enum\ReminderStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SetTaskReminderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'set_date' => ['bail', 'date'],
            'status' => ['bail', new Enum(ReminderStatus::class)],
        ];
    }

    public function getData(): SetTaskReminderData
    {
        /** @var User $user */
        $user = $this->user();

        $input = $this->input();

        $timezone = $user->timezone->utc ?? config('app.timezone');

        if (isset($input['set_date'])) {
            $input['set_date'] = $this->date('set_date', tz: $timezone)
                ->tz(config('app.timezone'))
                ->toDateTimeImmutable();
        }

        return SetTaskReminderData::from($input);
    }
}
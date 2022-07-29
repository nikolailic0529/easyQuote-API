<?php

namespace App\Http\Requests\Appointment;

use App\DTO\Appointment\CreateAppointmentReminderData;
use App\DTO\Appointment\UpdateAppointmentData;
use App\Enum\AppointmentTypeEnum;
use App\Enum\ReminderStatus;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use App\Models\SalesUnit;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateAppointment extends FormRequest
{
    protected readonly ?UpdateAppointmentData $updateAppointmentData;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'activity_type' => ['bail', 'required', new Enum(AppointmentTypeEnum::class)],
            'subject' => ['required', 'string', 'max:250'],
            'description' => ['present', 'nullable', 'string', 'max:5000'],
            'location' => ['present', 'nullable', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            'reminder.start_date_offset' => ['required_with:reminder', 'integer', 'min:0', 'max:'.(14 * 24 * 60 * 60)],
            'reminder.status' => ['required_with:reminder', new Enum(ReminderStatus::class)],

            'invitee_user_relations' => ['array'],
            'invitee_user_relations.*.user_id' => ['required', 'uuid', Rule::exists(User::class, 'id')->withoutTrashed()],

            'invitee_contact_relations' => ['array'],
            'invitee_contact_relations.*.contact_id' => ['required', 'uuid', Rule::exists(Contact::class, 'id')->withoutTrashed()],

            'company_relations' => ['array'],
            'company_relations.*.company_id' => ['required', 'uuid', Rule::exists(Company::class, 'id')->withoutTrashed()],

            'opportunity_relations' => ['array'],
            'opportunity_relations.*.opportunity_id' => ['required', 'uuid', Rule::exists(Opportunity::class, 'id')->withoutTrashed()],

            'contact_relations' => ['array'],
            'contact_relations.*.contact_id' => ['required', 'uuid', Rule::exists(Contact::class, 'id')->withoutTrashed()],

            'user_relations' => ['array'],
            'user_relations.*.user_id' => ['required', 'uuid', Rule::exists(User::class, 'id')->withoutTrashed()],

            'rescue_quote_relations' => ['array'],
            'rescue_quote_relations.*.quote_id' => ['required', 'uuid', Rule::exists(Quote::class, 'id')->withoutTrashed()],

            'worldwide_quote_relations' => ['array'],
            'worldwide_quote_relations.*.quote_id' => ['required', 'uuid', Rule::exists(WorldwideQuote::class, 'id')->withoutTrashed()],

            'attachment_relations' => ['array'],
            'attachment_relations.*.attachment_id' => ['required', 'uuid', Rule::exists(Attachment::class, 'id')],

            'sales_unit_id' => ['bail', 'required', 'uuid',
                Rule::exists(SalesUnit::class, (new SalesUnit())->getKeyName())->withoutTrashed()],
        ];
    }

    public function getUpdateAppointmentData(): UpdateAppointmentData
    {
        return $this->updateAppointmentData ??= value(function (): UpdateAppointmentData {

            $timezone = $this->user()->timezone->utc ?? config('app.timezone');

            return new UpdateAppointmentData([
                'sales_unit_id' => $this->input('sales_unit_id'),
                'activity_type' => AppointmentTypeEnum::from($this->input('activity_type')),
                'subject' => $this->input('subject'),
                'description' => (string)$this->input('description'),
                'location' => $this->input('location'),
                'start_date' => $this->date('start_date', tz: $timezone)
                    ->setTimezone(config('app.timezone'))
                    ->toDateTimeImmutable(),
                'end_date' => $this->date('end_date', tz: $timezone)
                    ->setTimezone(config('app.timezone'))
                    ->toDateTimeImmutable(),
                'invitee_user_relations' => $this->input('invitee_user_relations.*.user_id'),
                'invitee_contact_relations' => $this->input('invitee_contact_relations.*.contact_id'),
                'company_relations' => $this->input('company_relations.*.company_id'),
                'opportunity_relations' => $this->input('opportunity_relations.*.opportunity_id'),
                'contact_relations' => $this->input('contact_relations.*.contact_id'),
                'user_relations' => $this->input('user_relations.*.user_id'),
                'rescue_quote_relations' => $this->input('rescue_quote_relations.*.user_id'),
                'worldwide_quote_relations' => $this->input('worldwide_quote_relations.*.user_id'),
                'attachment_relations' => $this->input('attachment_relations.*.attachment_id'),
                'reminder' => $this->whenHas('reminder', static function (array $reminder): CreateAppointmentReminderData {
                    return new CreateAppointmentReminderData([
                        'start_date_offset' => (int)$reminder['start_date_offset'],
                        'status' => ReminderStatus::from($reminder['status']),
                    ]);
                }, static fn () => null),
            ]);
        });
    }
}

<?php

namespace App\DTO\Appointment;

use App\Enum\ReminderStatus;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateAppointmentReminderData extends DataTransferObject
{
    #[Constraints\PositiveOrZero]
    public int $start_date_offset;

    public ReminderStatus $status;
}
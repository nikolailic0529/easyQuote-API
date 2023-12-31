<?php

namespace App\Domain\Appointment\Enum;

enum AppointmentTypeEnum: string
{
    case Appointment = 'Appointment';
    case RecurringAppointment = 'Recurring Appointment';
    case Lunch = 'Lunch';
}

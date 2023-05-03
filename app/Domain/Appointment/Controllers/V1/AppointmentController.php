<?php

namespace App\Domain\Appointment\Controllers\V1;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Models\AppointmentReminder;
use App\Domain\Appointment\Requests\CreateAppointmentRequest;
use App\Domain\Appointment\Requests\SetAppointmentReminderRequest;
use App\Domain\Appointment\Requests\UpdateAppointmentRequest;
use App\Domain\Appointment\Resources\V1\AppointmentReminderResource;
use App\Domain\Appointment\Resources\V1\AppointmentWithIncludesResource;
use App\Domain\Appointment\Services\AppointmentEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppointmentController extends Controller
{
    /**
     * Show appointment.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showAppointment(Request $request, Appointment $appointment): AppointmentWithIncludesResource
    {
        $this->authorize('view', $appointment);

        return filter(AppointmentWithIncludesResource::make($appointment));
    }

    /**
     * Create appointment.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeAppointment(
        CreateAppointmentRequest $request,
        AppointmentEntityService $entityService
    ): AppointmentWithIncludesResource {
        $this->authorize('create', Appointment::class);

        $appointment = $entityService
            ->setCauser($request->user())
            ->createAppointment($request->getCreateAppointmentData(), $request->getModelHasAppointment());

        return filter(AppointmentWithIncludesResource::make($appointment));
    }

    /**
     * Update appointment.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateAppointment(
        UpdateAppointmentRequest $request,
        AppointmentEntityService $entityService,
        Appointment $appointment
    ): AppointmentWithIncludesResource {
        $this->authorize('update', $appointment);

        $appointment = $entityService
            ->setCauser($request->user())
            ->updateAppointment($appointment, $request->getUpdateAppointmentData());

        return filter(AppointmentWithIncludesResource::make($appointment));
    }

    /**
     * Set appointment reminder.
     *
     * @throws AuthorizationException
     */
    public function setAppointmentReminder(
        SetAppointmentReminderRequest $request,
        AppointmentEntityService $entityService,
        AppointmentReminder $reminder,
    ): AppointmentReminderResource {
        $this->authorize('update', $reminder);

        $entityService->updateReminder($reminder, $request->getData());

        return AppointmentReminderResource::make($reminder);
    }

    /**
     * Delete appointment reminder.
     *
     * @throws AuthorizationException
     */
    public function deleteAppointmentReminder(
        Request $request,
        AppointmentEntityService $entityService,
        AppointmentReminder $reminder
    ): Response {
        $this->authorize('delete', $reminder);

        $entityService
            ->setCauser($request->user())
            ->deleteReminder($reminder);

        return response()->noContent();
    }

    /**
     * Delete appointment.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteAppointment(
        Request $request,
        AppointmentEntityService $entityService,
        Appointment $appointment
    ): Response {
        $this->authorize('delete', $appointment);

        $entityService
            ->setCauser($request->user())
            ->deleteAppointment($appointment);

        return response()->noContent();
    }
}

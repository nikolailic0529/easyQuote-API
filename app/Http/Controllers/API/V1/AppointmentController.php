<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\CreateAppointmentRequest;
use App\Http\Requests\Appointment\SetAppointmentReminderRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Requests\Task\SetTaskReminderRequest;
use App\Http\Resources\Appointment\AppointmentReminderResource;
use App\Http\Resources\V1\Appointment\AppointmentWithIncludesResource;
use App\Http\Resources\V1\Task\TaskReminderResource;
use App\Models\Appointment\Appointment;
use App\Models\Appointment\AppointmentReminder;
use App\Models\Task\TaskReminder;
use App\Services\Appointment\AppointmentEntityService;
use App\Services\Task\TaskEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppointmentController extends Controller
{
    /**
     * Show appointment.
     *
     * @param  Request  $request
     * @param  Appointment  $appointment
     * @return AppointmentWithIncludesResource
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
     * @param  UpdateAppointmentRequest  $request
     * @param  AppointmentEntityService  $entityService
     * @param  \App\Models\Appointment\Appointment  $appointment
     * @return \App\Http\Resources\V1\Appointment\AppointmentWithIncludesResource
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
     * @param  SetAppointmentReminderRequest  $request
     * @param  AppointmentEntityService  $entityService
     * @param  AppointmentReminder  $reminder
     * @return AppointmentReminderResource
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
     * @param  Request  $request
     * @param  AppointmentEntityService  $entityService
     * @param  AppointmentReminder  $reminder
     * @return Response
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
     * @param  Request  $request
     * @param  AppointmentEntityService  $entityService
     * @param  Appointment  $appointment
     * @return Response
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
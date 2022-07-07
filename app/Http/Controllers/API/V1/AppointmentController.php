<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\CreateAppointment;
use App\Http\Requests\Appointment\UpdateAppointment;
use App\Http\Resources\V1\Appointment\AppointmentWithIncludesResource;
use App\Models\Appointment\Appointment;
use App\Services\Appointment\AppointmentEntityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AppointmentController extends Controller
{
    /**
     * Show appointment.
     *
     * @param Request $request
     * @param Appointment $appointment
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
    public function storeAppointment(CreateAppointment        $request,
                                     AppointmentEntityService $entityService): AppointmentWithIncludesResource
    {
        $this->authorize('create', Appointment::class);

        $appointment = $entityService
            ->setCauser($request->user())
            ->createAppointment($request->getCreateAppointmentData(), $request->getModelHasAppointment());

        return filter(AppointmentWithIncludesResource::make($appointment));
    }

    /**
     * Update appointment.
     *
     * @param UpdateAppointment $request
     * @param AppointmentEntityService $entityService
     * @param \App\Models\Appointment\Appointment $appointment
     * @return \App\Http\Resources\V1\Appointment\AppointmentWithIncludesResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateAppointment(UpdateAppointment        $request,
                                      AppointmentEntityService $entityService,
                                      Appointment              $appointment): AppointmentWithIncludesResource
    {
        $this->authorize('update', $appointment);

        $appointment = $entityService
            ->setCauser($request->user())
            ->updateAppointment($appointment, $request->getUpdateAppointmentData());

        return filter(AppointmentWithIncludesResource::make($appointment));
    }

    /**
     * Delete appointment.
     *
     * @param Request $request
     * @param AppointmentEntityService $entityService
     * @param Appointment $appointment
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function deleteAppointment(Request                  $request,
                                      AppointmentEntityService $entityService,
                                      Appointment              $appointment): Response
    {
        $this->authorize('delete', $appointment);

        $entityService
            ->setCauser($request->user())
            ->deleteAppointment($appointment);

        return response()->noContent();
    }
}
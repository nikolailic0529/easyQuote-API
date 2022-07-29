<?php

namespace App\Queries;

use App\Contracts\HasOwnAppointments;
use App\Models\Appointment\Appointment;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AppointmentQueries
{
    public function listAppointmentsLinkedToQuery(HasOwnAppointments&Model $modelHasAppointment,
                                                  Request                  $request = new Request()): Builder
    {
        $model = new Appointment();

        $query = $modelHasAppointment->ownAppointments()->getQuery()
            ->select([
                $model->getQualifiedKeyName(),

                ...$model->qualifyColumns([
                    'activity_type',
                    'subject',
                    'start_date',
                    'end_date',
                    'location',
                ]),

                $model->getQualifiedCreatedAtColumn(),
                $model->getQualifiedUpdatedAtColumn(),
            ]);

        return RequestQueryBuilder::for(
            $query, $request
        )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn())
            ->allowQuickSearchFields(
                'activity_type',
                'subject',
                'location'
            )
            ->process();
    }
}
<?php

namespace App\Domain\Appointment\Queries;

use App\Domain\Appointment\Contracts\HasOwnAppointments;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\User\Models\User;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class AppointmentQueries
{
    public function listAppointmentsLinkedToQuery(HasOwnAppointments&Model $modelHasAppointment,
                                                  Request $request = new Request()): Builder
    {
        $model = new Appointment();

        $query = $modelHasAppointment->ownAppointments()->getQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->owner()->getQualifiedForeignKeyName(),
                $model->salesUnit()->getQualifiedForeignKeyName(),

                ...$model->qualifyColumns([
                    'activity_type',
                    'subject',
                    'start_date',
                    'end_date',
                    'location',
                ]),

                $model->getQualifiedCreatedAtColumn(),
                $model->getQualifiedUpdatedAtColumn(),
            ])
            ->with(['owner' => static function (Relation $relation): void {
                $model = new User();

                $relation->select([
                    $model->getQualifiedKeyName(),
                    $model->qualifyColumn('user_fullname'),
                    $model->qualifyColumn('first_name'),
                    $model->qualifyColumn('middle_name'),
                    $model->qualifyColumn('last_name'),
                    $model->qualifyColumn('email'),
                ]);
            }]);

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

<?php

namespace App\Services\Invitation;

use App\DTO\Invitation\CreateInvitationData;
use App\Models\Collaboration\Invitation;
use Illuminate\Database\ConnectionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class InvitationEntityService
{
    public function __construct(protected ConnectionInterface $connection,
                                protected ValidatorInterface  $validator)
    {
    }

    public function createInvitation(CreateInvitationData $data): Invitation
    {
        $violations = $this->validator->validate($data);

        count($violations) && throw new ValidationFailedException($data, $violations);

        return tap(new Invitation(), function (Invitation $invitation) use ($data): void {
            $invitation->forceFill($data->except('sales_units', 'companies')->toArray());
            $invitation->setRelation(
                $invitation->salesUnits()->getRelationName(),
                $invitation->salesUnits()->getRelated()->findMany($data->sales_units->toCollection()->pluck('id'))
            );
            $invitation->setRelation(
                $invitation->companies()->getRelationName(),
                $invitation->companies()->getRelated()->findMany($data->companies->toCollection()->pluck('id'))
            );

            $this->connection->transaction(static function () use ($invitation): void {
                $invitation->save();
                $invitation->salesUnits()->attach($invitation->salesUnits);
                $invitation->companies()->attach($invitation->companies);
            });
        });
    }
}
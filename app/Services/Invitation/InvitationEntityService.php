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
            $invitation->email = $data->email;
            $invitation->role()->associate($data->role_id);
            $invitation->team()->associate($data->team_id);
            $invitation->setRelation(
                $invitation->salesUnits()->getRelationName(),
                $invitation->salesUnits()->getRelated()->newQuery()->whereKey(data_get($data->sales_units, '*.id'))->get()
            );
            $invitation->host = $data->host;

            $this->connection->transaction(static function () use ($invitation): void {
                $invitation->save();
                $invitation->salesUnits()->attach($invitation->salesUnits);
            });
        });
    }
}
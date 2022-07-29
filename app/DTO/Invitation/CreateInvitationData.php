<?php

namespace App\DTO\Invitation;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateInvitationData extends DataTransferObject
{
    #[Constraints\Email]
    public string $email;

    #[Constraints\Uuid]
    public string $role_id;

    #[Constraints\Uuid]
    public ?string $team_id = null;

    #[Constraints\Uuid]
    public ?string $sales_unit_id = null;

    public ?string $host = null;

    /** @var \App\DTO\SalesUnit\CreateSalesUnitRelationData[] **/
    public array $sales_units = [];
}
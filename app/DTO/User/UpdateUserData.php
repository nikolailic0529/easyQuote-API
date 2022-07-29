<?php

namespace App\DTO\User;

use Spatie\DataTransferObject\DataTransferObject;

final class UpdateUserData extends DataTransferObject
{
    public string $first_name;
    public ?string $middle_name;
    public string $last_name;
    public ?string $phone;
    public string $timezone_id;
    /** @var \App\DTO\SalesUnit\CreateSalesUnitRelationData[] */
    public array $sales_units;
    public string $role_id;
    public string $team_id;
}
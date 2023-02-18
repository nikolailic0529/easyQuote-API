<?php

namespace App\Domain\Company\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class UpdateCompanyContactData extends DataTransferObject
{
    public string $first_name;

    public string $last_name;

    public ?string $phone;

    public ?string $mobile;

    public ?string $email;

    public ?string $job_title;

    public bool $is_verified;
}

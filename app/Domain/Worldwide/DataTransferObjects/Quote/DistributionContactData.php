<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Spatie\DataTransferObject\DataTransferObject;

final class DistributionContactData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $contact_id = null;

    public string $contact_type;

    public string $first_name;

    public string $last_name;

    public ?string $email;

    public ?string $mobile;

    public ?string $phone;

    public ?string $job_title;

    public bool $is_verified;

    public bool $is_default;
}

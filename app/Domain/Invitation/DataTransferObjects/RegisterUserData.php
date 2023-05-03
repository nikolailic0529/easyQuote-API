<?php

namespace App\Domain\Invitation\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class RegisterUserData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     */
    public string $first_name;

    public ?string $middle_name;

    /**
     * @Constraints\NotBlank
     */
    public string $last_name;

    /**
     * @Constraints\NotBlank()
     */
    public string $password;

    public ?string $phone;

    /**
     * @Constraints\Uuid
     */
    public string $timezone_id;

    protected array $exceptKeys = ['password'];
}

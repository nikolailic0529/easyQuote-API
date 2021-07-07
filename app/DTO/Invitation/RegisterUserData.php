<?php

namespace App\DTO\Invitation;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class RegisterUserData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $first_name;

    public ?string $middle_name;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $last_name;

    /**
     * @Constraints\NotBlank()
     *
     * @var string
     */
    public string $password;

    public ?string $phone;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $timezone_id;

    protected array $exceptKeys = ['password'];
}
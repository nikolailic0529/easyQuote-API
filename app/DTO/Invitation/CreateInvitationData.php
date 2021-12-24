<?php

namespace App\DTO\Invitation;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateInvitationData extends DataTransferObject
{
    /**
     * @Constraints\Email()
     *
     * @var string
     */
    public string $email;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $role_id;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $team_id = null;

    public ?string $host = null;
}
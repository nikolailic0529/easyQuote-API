<?php

namespace App\Domain\Pipeliner\Integration\Models;

class CreateAppointmentContactInviteesRelationNoAppointmentBackrefInput extends BaseInput
{
    public function __construct(
        public readonly string $contactId,
        public readonly string $email,
    ) {
    }
}

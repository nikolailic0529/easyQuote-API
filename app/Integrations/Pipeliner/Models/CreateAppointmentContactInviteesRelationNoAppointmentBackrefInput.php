<?php

namespace App\Integrations\Pipeliner\Models;

class CreateAppointmentContactInviteesRelationNoAppointmentBackrefInput extends BaseInput
{
    public function __construct(
        public readonly string $contactId,
        public readonly string $email,
    ) {
    }
}
<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InviteeResponseEnum;
use App\Domain\Pipeliner\Integration\Enum\InviteeTypeEnum;

class ActivityContactInviteesRelationEntity
{
    public function __construct(
        public readonly string $id,
        public readonly InviteeTypeEnum $inviteeType,
        public readonly InviteeResponseEnum $response,
        public readonly string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $contactId
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            inviteeType: InviteeTypeEnum::from($array['inviteeType']),
            response: InviteeResponseEnum::from($array['response']),
            email: $array['email'],
            firstName: $array['firstName'],
            lastName: $array['lastName'],
            contactId: $array['contactId']
        );
    }
}

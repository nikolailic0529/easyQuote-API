<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Attributes\SerializeWith;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Serializers\DateTimeSerializer;
use DateTimeImmutable;
use DateTimeInterface;

final class CreateAppointmentInput extends BaseInput
{
    public function __construct(public readonly string                                                                                 $subject,
                                #[SerializeWith(DateTimeSerializer::class, DateTimeInterface::ATOM)] public readonly DateTimeImmutable $startDate,
                                #[SerializeWith(DateTimeSerializer::class, DateTimeInterface::ATOM)] public readonly DateTimeImmutable $endDate,
                                public readonly string|InputValueEnum                                                                  $unitId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                                                                  $activityTypeId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                                                                  $ownerId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                                                                  $description = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                                                                  $location = InputValueEnum::Miss,
                                public readonly CreateActivityAccountRelationInputCollection|InputValueEnum                            $accountRelations = InputValueEnum::Miss,
                                public readonly CreateActivityContactRelationInputCollection|InputValueEnum                            $contactRelations = InputValueEnum::Miss,
                                public readonly CreateActivityLeadOpptyRelationInputCollection|InputValueEnum                          $opportunityRelations = InputValueEnum::Miss,
                                public readonly CreateActivityClientRelationInputCollection|InputValueEnum                             $inviteesClients = InputValueEnum::Miss,
                                public readonly CreateActivityContactRelationInputCollection|InputValueEnum                            $inviteesContacts = InputValueEnum::Miss,
                                public readonly CreateAppointmentReminderInput|InputValueEnum                                          $reminder = InputValueEnum::Miss)
    {
    }
}
<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Attributes\SerializeWith;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;
use App\Domain\Pipeliner\Integration\Serializers\DateTimeSerializer;

final class UpdateAppointmentInput extends BaseInput
{
    public function __construct(
        public readonly string $id,
        public readonly string|InputValueEnum $unitId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $ownerId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $activityTypeId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $subject = InputValueEnum::Miss,
        #[SerializeWith(DateTimeSerializer::class, \DateTimeInterface::ATOM)] public readonly \DateTimeImmutable|InputValueEnum $startDate = InputValueEnum::Miss,
        #[SerializeWith(DateTimeSerializer::class, \DateTimeInterface::ATOM)] public readonly \DateTimeImmutable|InputValueEnum $endDate = InputValueEnum::Miss,
        public readonly string|InputValueEnum $description = InputValueEnum::Miss,
        public readonly string|InputValueEnum $location = InputValueEnum::Miss,
        public readonly CreateActivityAccountRelationInputCollection|InputValueEnum $accountRelations = InputValueEnum::Miss,
        public readonly CreateActivityContactRelationInputCollection|InputValueEnum $contactRelations = InputValueEnum::Miss,
        public readonly CreateActivityLeadOpptyRelationInputCollection|InputValueEnum $opportunityRelations = InputValueEnum::Miss,
        public readonly CreateActivityClientRelationInputCollection|InputValueEnum $inviteesClients = InputValueEnum::Miss,
        public readonly CreateAppointmentContactInviteesRelationNoAppointmentBackrefInputCollection|InputValueEnum $inviteesContacts = InputValueEnum::Miss,
        public readonly CreateAppointmentReminderInput|InputValueEnum $reminder = InputValueEnum::Miss,
        public readonly CreateCloudObjectRelationInputCollection|InputValueEnum $documents = InputValueEnum::Miss,
    ) {
    }
}

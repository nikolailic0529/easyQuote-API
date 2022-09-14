<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Attributes\SerializeWith;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Enum\OpportunityLabelFlag;
use App\Integrations\Pipeliner\Serializers\DateTimeSerializer;
use DateTimeImmutable;

final class CreateOpportunityInput extends BaseInput
{
    public function __construct(
        #[SerializeWith(DateTimeSerializer::class, 'Y-m-d')] public readonly DateTimeImmutable $closingDate,
        public readonly string $name,
        public readonly string $ownerId,
        public readonly string $stepId,
        public readonly CreateOpptyAccountRelationInputCollection|InputValueEnum $accountRelations,
        public readonly CreateContactRelationInputCollection|InputValueEnum $contactRelations = InputValueEnum::Miss,
        public readonly CreateCloudObjectRelationInputCollection|InputValueEnum $documents = InputValueEnum::Miss,
        public readonly DateTimeImmutable|InputValueEnum $created = InputValueEnum::Miss,
        public readonly DateTimeImmutable|InputValueEnum $modified = InputValueEnum::Miss,
        public readonly string|InputValueEnum $description = InputValueEnum::Miss,
        public readonly bool|InputValueEnum $isArchived = InputValueEnum::Miss,
        public readonly bool|InputValueEnum $isValueAutoCalculate = InputValueEnum::Miss,
        public readonly OpportunityLabelFlag|InputValueEnum $labelFlag = InputValueEnum::Miss,
        public readonly string|InputValueEnum $productCurrencyId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $productPriceListId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $quickAccountEmail = InputValueEnum::Miss,
        public readonly string|InputValueEnum $quickAccountPhone = InputValueEnum::Miss,
        public readonly string|InputValueEnum $quickContactName = InputValueEnum::Miss,
        public readonly string|InputValueEnum $quickEmail = InputValueEnum::Miss,
        public readonly int|InputValueEnum $ranking = InputValueEnum::Miss,
        public readonly string|InputValueEnum $reasonOfCloseDescription = InputValueEnum::Miss,
        public readonly string|InputValueEnum $reasonOfCloseId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $unitId = InputValueEnum::Miss,
        public readonly bool|InputValueEnum $wasQualified = InputValueEnum::Miss,
        public readonly string|InputValueEnum $customFields = InputValueEnum::Miss,
        public readonly int|InputValueEnum $revision = InputValueEnum::Miss,
        public readonly CurrencyForeignFieldInput|InputValueEnum $value = InputValueEnum::Miss,
        public readonly DateTimeImmutable|InputValueEnum $wonDate = InputValueEnum::Miss,
        public readonly DateTimeImmutable|InputValueEnum $lostDate = InputValueEnum::Miss
    ) {
    }
}
<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\GenderEnum;
use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

class UpdateContactInput extends BaseInput
{
    public function __construct(
        public readonly string $id,
        public readonly string|InputValueEnum $ownerId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $accountPosition = InputValueEnum::Miss,
        public readonly CreateContactRelationInputCollection|InputValueEnum $accountRelations = InputValueEnum::Miss,
        public readonly string|InputValueEnum $address = InputValueEnum::Miss,
        public readonly string|InputValueEnum $city = InputValueEnum::Miss,
        public readonly string|InputValueEnum $clientMutationId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $comments = InputValueEnum::Miss,
        public readonly string|InputValueEnum $contactTypeId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $country = InputValueEnum::Miss,
        public readonly \DateTimeImmutable|InputValueEnum $created = InputValueEnum::Miss,
        public readonly string|InputValueEnum $customFields = InputValueEnum::Miss,
        public readonly string|InputValueEnum $email1 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $email2 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $email3 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $email4 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $email5 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $phone1 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $phone2 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $phone3 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $phone4 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $phone5 = InputValueEnum::Miss,
        public readonly string|InputValueEnum $firstName = InputValueEnum::Miss,
        public readonly string|InputValueEnum $middleName = InputValueEnum::Miss,
        public readonly string|InputValueEnum $lastName = InputValueEnum::Miss,
        public readonly string|InputValueEnum $position = InputValueEnum::Miss,
        public readonly string|InputValueEnum $quickAccountName = InputValueEnum::Miss,
        public readonly string|InputValueEnum $stateProvince = InputValueEnum::Miss,
        public readonly string|InputValueEnum $title = InputValueEnum::Miss,
        public readonly string|InputValueEnum $unitId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $zipCode = InputValueEnum::Miss,
        public readonly int|InputValueEnum $revision = InputValueEnum::Miss,
        public readonly GenderEnum|InputValueEnum $gender = InputValueEnum::Miss,
        public readonly bool|InputValueEnum $isUnsubscribed = InputValueEnum::Miss
    ) {
    }

    public function getModifiedFields(): array
    {
        $fields = [];

        foreach ($this->getProperties() as $property) {
            $name = $property->getName();
            $value = $this->{$name};

            if ('id' === $name || InputValueEnum::Miss === $value) {
                continue;
            }

            $fields[$name] = $value;
        }

        return $fields;
    }
}

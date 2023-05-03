<?php

namespace App\Domain\Pipeliner\Integration\Models;

use App\Domain\Pipeliner\Integration\Enum\InputValueEnum;

class UpdateAccountInput extends BaseInput
{
    public function __construct(
        public readonly string $id,
        public readonly string|InputValueEnum $name = InputValueEnum::Miss,
        public readonly string|InputValueEnum $ownerId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $parentAccountId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $parentAccountRelationTypeId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $accountTypeId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $address = InputValueEnum::Miss,
        public readonly string|InputValueEnum $city = InputValueEnum::Miss,
        public readonly string|InputValueEnum $comments = InputValueEnum::Miss,
        public readonly string|InputValueEnum $country = InputValueEnum::Miss,
        public readonly \DateTimeImmutable|InputValueEnum $created = InputValueEnum::Miss,
        public readonly \DateTimeImmutable|InputValueEnum $modified = InputValueEnum::Miss,
        public readonly string|InputValueEnum $customerTypeId = InputValueEnum::Miss,
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
        public readonly string|InputValueEnum $homePage = InputValueEnum::Miss,
        public readonly string|InputValueEnum $industryId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $pictureId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $stateProvince = InputValueEnum::Miss,
        public readonly string|InputValueEnum $unitId = InputValueEnum::Miss,
        public readonly string|InputValueEnum $zipCode = InputValueEnum::Miss,
        public readonly bool|InputValueEnum $isUnsubscribed = InputValueEnum::Miss,
        public readonly CreateCloudObjectInput|InputValueEnum $picture = InputValueEnum::Miss,
        public readonly CreateCloudObjectRelationInputCollection|InputValueEnum $documents = InputValueEnum::Miss,
        public readonly CreateAccountSharingClientRelationInputCollection|InputValueEnum $sharingClients = InputValueEnum::Miss,
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

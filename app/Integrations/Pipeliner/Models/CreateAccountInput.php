<?php

namespace App\Integrations\Pipeliner\Models;

use App\Integrations\Pipeliner\Enum\InputValueEnum;

class CreateAccountInput extends BaseInput
{
    public function __construct(public readonly string                                $name,
                                public readonly string                                $ownerId,
                                public readonly string|InputValueEnum                 $parentAccountId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $parentAccountRelationTypeId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $accountTypeId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $address = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $city = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $comments = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $country = InputValueEnum::Miss,
                                public readonly \DateTimeImmutable|InputValueEnum     $created = InputValueEnum::Miss,
                                public readonly \DateTimeImmutable|InputValueEnum     $modified = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $customerTypeId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $customFields = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $email1 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $email2 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $email3 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $email4 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $email5 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $phone1 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $phone2 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $phone3 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $phone4 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $phone5 = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $homePage = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $industryId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $pictureId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $stateProvince = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $unitId = InputValueEnum::Miss,
                                public readonly string|InputValueEnum                 $zipCode = InputValueEnum::Miss,
                                public readonly bool|InputValueEnum                   $isUnsubscribed = InputValueEnum::Miss,
                                public readonly CreateCloudObjectInput|InputValueEnum $picture = InputValueEnum::Miss)
    {
    }
}
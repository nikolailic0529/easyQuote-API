<?php

namespace App\Integrations\Pipeliner\Models;

use App\Helpers\Enum;
use App\Integrations\Pipeliner\Enum\GenderEnum;
use JetBrains\PhpStorm\Pure;

/**
 * @property-read ContactAccountRelationEntity[] $accountRelations
 */
class ContactEntity
{
    public function __construct(public readonly string           $id,
                                public readonly ?ClientEntity    $owner,
                                public readonly ?SalesUnitEntity $unit,
                                public readonly string           $address,
                                public readonly string           $email1,
                                public readonly string           $phone1,
                                public readonly string           $phone2,
                                public readonly string           $title,
                                public readonly string           $formattedName,
                                public readonly string           $firstName,
                                public readonly string           $middleName,
                                public readonly string           $lastName,
                                public readonly string           $zipCode,
                                public readonly string           $stateProvince,
                                public readonly string           $city,
                                public readonly string           $country,
                                public readonly GenderEnum       $gender,
                                public readonly array            $customFields,
                                public readonly array            $accountRelations)
    {
    }

    #[Pure]
    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            owner: ClientEntity::tryFromArray($array['owner']),
            unit: SalesUnitEntity::tryFromArray($array['unit']),
            address: $array['address'],
            email1: $array['email1'],
            phone1: $array['phone1'],
            phone2: $array['phone2'],
            title: $array['title'],
            formattedName: $array['formattedName'],
            firstName: $array['firstName'],
            middleName: $array['middleName'],
            lastName: $array['lastName'],
            zipCode: $array['zipCode'],
            stateProvince: $array['stateProvince'],
            city: $array['city'],
            country: $array['country'],
            gender: Enum::fromKey(GenderEnum::class, $array['gender']),
            customFields: json_decode($array['customFields'] ?? '{}', true),
            accountRelations: array_map(ContactAccountRelationEntity::fromArray(...), array_column($array['accountRelations']['edges'], 'node')),
        );
    }

    public static function tryFromArray(?array $array): ?static
    {
        if (is_null($array)) {
            return null;
        }

        return self::fromArray($array);
    }
}
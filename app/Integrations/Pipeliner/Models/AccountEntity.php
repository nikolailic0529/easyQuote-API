<?php

namespace App\Integrations\Pipeliner\Models;

class AccountEntity
{
    public function __construct(public readonly string             $id,
                                public readonly string             $formattedName,
                                public readonly string             $email1,
                                public readonly string             $phone1,
                                public readonly string             $address,
                                public readonly string             $city,
                                public readonly string             $zipCode,
                                public readonly string             $stateProvince,
                                public readonly string             $country,
                                public readonly string             $homePage,
                                public readonly ?CloudObjectEntity $picture,
                                public readonly array              $customFields,
                                public readonly \DateTimeImmutable $created,
                                public readonly \DateTimeImmutable $modified,
                                public readonly int                $revision)
    {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            formattedName: $array['formattedName'],
            email1: $array['email1'],
            phone1: $array['phone1'],
            address: $array['address'],
            city: $array['city'],
            zipCode: $array['zipCode'],
            stateProvince: $array['stateProvince'],
            country: $array['country'],
            homePage: $array['homePage'],
            picture: isset($array['picture']) ? CloudObjectEntity::fromArray($array['picture']) : null,
            customFields: json_decode($array['customFields'], true),
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            revision: $array['revision'],
        );
    }
}
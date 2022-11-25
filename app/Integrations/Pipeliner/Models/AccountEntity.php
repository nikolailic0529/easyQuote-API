<?php

namespace App\Integrations\Pipeliner\Models;

use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

/**
 * @property-read CloudObjectEntity[] $documents
 */
class AccountEntity
{
    public function __construct(
        public readonly string $id,
        public readonly ClientEntity|null $owner,
        public readonly SalesUnitEntity|null $unit,
        public readonly string $name,
        public readonly string $formattedName,
        public readonly string $email1,
        public readonly string $phone1,
        public readonly string $address,
        public readonly string $city,
        public readonly string $zipCode,
        public readonly string $stateProvince,
        public readonly string $country,
        public readonly string $homePage,
        public readonly ?DataEntity $customerType,
        public readonly ?CloudObjectEntity $picture,
        public readonly array $customFields,
        public readonly array $documents,
        public readonly DateTimeImmutable $created,
        public readonly DateTimeImmutable $modified,
        public readonly int $revision
    ) {
    }

    public static function fromArray(array $array): static
    {
        return new static(
            id: $array['id'],
            owner: ClientEntity::tryFromArray($array['owner']),
            unit: SalesUnitEntity::tryFromArray($array['unit']),
            name: $array['name'],
            formattedName: $array['formattedName'],
            email1: $array['email1'],
            phone1: $array['phone1'],
            address: $array['address'],
            city: $array['city'],
            zipCode: $array['zipCode'],
            stateProvince: $array['stateProvince'],
            country: $array['country'],
            homePage: $array['homePage'],
            customerType: DataEntity::tryFromArray($array['customerType']),
            picture: isset($array['picture']) ? CloudObjectEntity::fromArray($array['picture']) : null,
            customFields: json_decode($array['customFields'], true),
            documents: array_map(CloudObjectEntity::fromArray(...),
                data_get($array, 'documents.edges.*.node.cloudObject', [])),
            created: Entity::parseDateTime($array['created']),
            modified: Entity::parseDateTime($array['modified']),
            revision: $array['revision'],
        );
    }

    public static function tryFromArray(?array $array): ?static
    {
        return isset($array) ? static::fromArray($array) : null;
    }
}
<?php

namespace App\DTO\Address;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class CreateAddressData extends Data
{
    public function __construct(
        public readonly string $address_type,
        public readonly ?string $address_1,
        public readonly ?string $address_2,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $state_code,
        public readonly ?string $post_code,
        public readonly ?string $country_id,
        public readonly ?string $contact_id,
        #[DataCollectionOf(CreateAddressCompanyRelationNoBackrefData::class)]
        public readonly DataCollection|Optional $company_relations,
    ) {
    }


}
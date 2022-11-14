<?php

namespace App\DTO\Address;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class UpdateAddressData extends Data
{
    public function __construct(
        public string $address_type,
        public ?string $address_1,
        public ?string $address_2,
        public ?string $city,
        public ?string $state,
        public ?string $state_code,
        public ?string $post_code,
        public ?string $country_id,
        public string|Optional $contact_id,
        #[DataCollectionOf(CreateAddressCompanyRelationNoBackrefData::class)]
        public readonly DataCollection|Optional $company_relations,
    ) {
    }
}
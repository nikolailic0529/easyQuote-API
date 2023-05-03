<?php

namespace App\Domain\Address\DataTransferObjects;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class UpdateAddressData extends Data
{
    public function __construct(
        public string|Optional $address_type,
        public string|null|Optional $address_1,
        public string|null|Optional $address_2,
        public string|null|Optional $city,
        public string|null|Optional $state,
        public string|null|Optional $state_code,
        public string|null|Optional $post_code,
        public string|null|Optional $country_id,
        public string|Optional $contact_id,
        #[DataCollectionOf(CreateAddressCompanyRelationNoBackrefData::class)]
        public readonly DataCollection|Optional $company_relations,
    ) {
    }
}

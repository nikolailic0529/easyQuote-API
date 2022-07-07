<?php

namespace App\Services\Address;

use App\Helpers\Enum;
use App\Integrations\Pipeliner\Enum\GenderEnum;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Models\ContactEntity;
use App\Integrations\Pipeliner\Models\CreateContactInput;
use App\Integrations\Pipeliner\Models\DataEntity;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\FieldFilterInput;
use App\Integrations\Pipeliner\Models\UpdateContactInput;
use App\Models\Address;
use App\Services\Pipeliner\PipelinerClientLookupService;
use App\Services\Pipeliner\RuntimeCachedFieldEntityResolver;

class AddressDataMapper
{
    public function __construct(protected PipelinerClientLookupService     $pipelinerClientLookupService,
                                protected RuntimeCachedFieldEntityResolver $pipelinerFieldResolver)
    {
    }


    public function mapPipelinerCreateContactInput(Address $address): CreateContactInput
    {
        $ownerId = null !== $address->user
            ? $address->user?->pl_reference
            : $this->pipelinerClientLookupService->findDefaultEntity()->id;

        return new CreateContactInput(
            ownerId: $ownerId,
            address: (string)$address->address_1,
            city: (string)$address->city,
            country: (string)$address->country?->name,
            email1: (string)$address->contact?->email,
            phone1: (string)$address->contact?->phone,
            phone2: (string)$address->contact?->mobile,
            firstName: (string)$address->contact?->first_name,
            lastName: (string)$address->contact?->last_name,
            stateProvince: (string)$address->state,
//            title: (string)$address->contact?->job_title,
            zipCode: (string)$address->post_code,
            gender: isset($address->contact?->gender)
                ? Enum::fromKey(GenderEnum::class, $address->contact->gender->name)
                : InputValueEnum::Miss,
            customFields: json_encode($this->projectAddressAttrsToCustomFields($address)),
        );
    }

    public function mapPipelinerUpdateContactInput(Address $address, ContactEntity $contactEntity): UpdateContactInput
    {
        $oldFields = [
            'address' => $contactEntity->address,
            'city' => $contactEntity->city,
            'country' => $contactEntity->country,
            'email1' => $contactEntity->email1,
            'phone1' => $contactEntity->phone1,
            'phone2' => $contactEntity->phone2,
            'firstName' => $contactEntity->firstName,
            'lastName' => $contactEntity->lastName,
            'stateProvince' => $contactEntity->stateProvince,
//            'title' => $contactEntity->title,
            'zipCode' => $contactEntity->zipCode,
            'gender' => $contactEntity->gender->name,
            'customFields' => json_encode($contactEntity->customFields),
        ];

        $newFields = [
            'address' => (string)$address->address_1,
            'city' => (string)$address->city,
            'country' => (string)$address->country?->name,
            'email1' => (string)$address->contact?->email,
            'phone1' => (string)$address->contact?->phone,
            'phone2' => (string)$address->contact?->mobile,
            'firstName' => (string)$address->contact?->first_name,
            'lastName' => (string)$address->contact?->last_name,
            'stateProvince' => (string)$address->state,
//            'title' => (string)$address->contact?->job_title,
            'zipCode' => (string)$address->post_code,
            'gender' => isset($address->contact?->gender)
                ? $address->contact->gender->name
                : $contactEntity->gender->name,
            'customFields' => json_encode(array_merge($contactEntity->customFields, $this->projectAddressAttrsToCustomFields($address))),
        ];

        $changedFields = array_diff_assoc($newFields, $oldFields);

        if (key_exists('gender', $changedFields)) {
            $changedFields['gender'] = Enum::fromKey(GenderEnum::class, $changedFields['gender']);
        }

        return new UpdateContactInput(
            $contactEntity->id,
            ...$changedFields
        );
    }

    public function projectAddressAttrsToCustomFields(Address $address): array
    {
        $customFields = [];


        $typeId = $this->resolveDataEntityByOptionName('Contact', 'cf_type1_id', $address->address_type)?->id;

        if (null !== $typeId) {
            $customFields['cfType1Id'] = $typeId;
        }

        $customFields['cfAddressTwo'] = $address->address_2;
        $customFields['cfStateCode1'] = $address->state_code;

        if (null !== $address->contact) {
            $customFields['cfJobTitle'] = $address->contact?->job_title;
        }

        return $customFields;
    }

    public function resolveDataEntityByOptionName(string $entityName, string $apiName, ?string $optionName): ?DataEntity
    {
        $field = ($this->pipelinerFieldResolver)(
            FieldFilterInput::new()
                ->entityName(EntityFilterStringField::ieq($entityName))
                ->apiName(EntityFilterStringField::ieq($apiName))
        );

        if (is_null($field)) {
            return null;
        }

        foreach ($field->dataSet as $dataEntity) {
            if ($optionName === $dataEntity->optionName) {
                return $dataEntity;
            }
        }

        return null;
    }
}
<?php

namespace App\Services\Contact;

use App\Helpers\Enum;
use App\Integrations\Pipeliner\Enum\GenderEnum;
use App\Integrations\Pipeliner\Enum\InputValueEnum;
use App\Integrations\Pipeliner\Models\ContactEntity;
use App\Integrations\Pipeliner\Models\CreateContactInput;
use App\Integrations\Pipeliner\Models\DataEntity;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\FieldFilterInput;
use App\Integrations\Pipeliner\Models\UpdateContactInput;
use App\Models\Contact;
use App\Services\Pipeliner\PipelinerClientLookupService;
use App\Services\Pipeliner\RuntimeCachedFieldEntityResolver;

class ContactDataMapper
{
    public function __construct(protected PipelinerClientLookupService     $pipelinerClientLookupService,
                                protected RuntimeCachedFieldEntityResolver $pipelinerFieldResolver)
    {
    }


    public function mapPipelinerCreateContactInput(Contact $contact): CreateContactInput
    {
        $ownerId = null !== $contact->user
            ? $contact->user?->pl_reference
            : $this->pipelinerClientLookupService->findDefaultEntity()->id;

        $address = $contact->address;

        return new CreateContactInput(
            ownerId: $ownerId,
            address: (string)$address->address_1,
            city: (string)$address->city,
            country: (string)$address->country?->name,
            email1: (string)$contact->email,
            phone1: (string)$contact->phone,
            phone2: (string)$contact->mobile,
            firstName: (string)$contact->first_name,
            lastName: (string)$contact->last_name,
            stateProvince: (string)$address->state,
            unitId: $contact->salesUnit?->pl_reference ?? InputValueEnum::Miss,
            zipCode: (string)$address->post_code,
            gender: isset($contact->gender)
                ? Enum::fromKey(GenderEnum::class, $contact->gender->name)
                : InputValueEnum::Miss,
            customFields: json_encode($this->projectAddressAttrsToCustomFields($contact))
        );
    }

    public function mapPipelinerUpdateContactInput(Contact $contact, ContactEntity $contactEntity): UpdateContactInput
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
            'unitId' => $contactEntity->unit?->id,
            'customFields' => json_encode($contactEntity->customFields),
        ];

        $newFields = [
            'address' => (string)$contact->address?->address_1,
            'city' => (string)$contact->address?->city,
            'country' => (string)$contact->address?->country?->name,
            'email1' => (string)$contact->email,
            'phone1' => (string)$contact->phone,
            'phone2' => (string)$contact->mobile,
            'firstName' => (string)$contact->first_name,
            'lastName' => (string)$contact->last_name,
            'stateProvince' => (string)$contact->address?->state,
            'zipCode' => (string)$contact->address?->post_code,
            'unitId' => $contact->salesUnit?->pl_reference ?? InputValueEnum::Miss,
            'gender' => isset($contact->gender)
                ? $contact->gender->name
                : $contactEntity->gender->name,
            'customFields' => json_encode(array_merge($contactEntity->customFields, $this->projectAddressAttrsToCustomFields($contact))),
        ];

        $changedFields = array_udiff_assoc($newFields, $oldFields, function (mixed $a, mixed $b): int {
            if ($a === null || $b === null) {
                return $a === $b ? 0 : 1;
            }

            if ($a === InputValueEnum::Miss || $b === InputValueEnum::Miss) {
                return $a === $b ? 0 : 1;
            }

            return $a <=> $b;
        });

        if (key_exists('gender', $changedFields)) {
            $changedFields['gender'] = Enum::fromKey(GenderEnum::class, $changedFields['gender']);
        }

        return new UpdateContactInput(
            $contactEntity->id,
            ...$changedFields
        );
    }

    public function projectAddressAttrsToCustomFields(Contact $contact): array
    {
        $customFields = [];

        $typeId = $this->resolveDataEntityByOptionName('Contact', 'cf_type1_id', $contact->contact_type)?->id;

        if (null !== $typeId) {
            $customFields['cfType1Id'] = $typeId;
        }

        $customFields['cfAddressTwo'] = $contact->address?->address_2;
        $customFields['cfStateCode1'] = $contact->address?->state_code;
        $customFields['cfJobTitle'] = $contact->job_title;

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
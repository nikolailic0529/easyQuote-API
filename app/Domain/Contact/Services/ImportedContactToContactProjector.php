<?php

namespace App\Domain\Contact\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Contact\Models\Contact;
use App\Domain\Contact\Models\ImportedContact;
use App\Domain\Language\Models\Language;

class ImportedContactToContactProjector
{
    public function __invoke(ImportedContact $importedContact, ?Address $relatedAddress): Contact
    {
        return tap(new Contact(), static function (Contact $contact) use ($relatedAddress, $importedContact): void {
            $contact->setId();
            $contact->pl_reference = $importedContact->pl_reference;
            $contact->user()->associate($importedContact->owner);
            $contact->address()->associate($relatedAddress);
            $contact->salesUnit()->associate($importedContact->salesUnit);
            $contact->contact_type = $importedContact->contact_type;
            $contact->gender = $importedContact->gender;
            $contact->first_name = $importedContact->first_name;
            $contact->last_name = $importedContact->last_name;
            $contact->email = $importedContact->email;
            $contact->phone = $importedContact->phone;
            $contact->mobile = $importedContact->phone_2;
            $contact->job_title = $importedContact->job_title;
            $contact->contact_name = $importedContact->contact_name;

            if ($importedContact->language_name) {
                $contact->language()->associate(
                    Language::query()
                        ->where('name', $importedContact->language_name)
                        ->first()
                );
            }

            $contact->updateTimestamps();
        });
    }
}

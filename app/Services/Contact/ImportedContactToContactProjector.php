<?php

namespace App\Services\Contact;

use App\Models\Contact;
use App\Models\ImportedContact;
use Webpatser\Uuid\Uuid;

class ImportedContactToContactProjector
{
    public function __invoke(ImportedContact $importedContact): Contact
    {
        return tap(new Contact(), function (Contact $contact) use ($importedContact): void {
            $contact->{$contact->getKeyName()} = (string)Uuid::generate(4);
            $contact->pl_reference = $importedContact->pl_reference;
            $contact->user()->associate($importedContact->owner);
            $contact->contact_type = $importedContact->contact_type;
            $contact->gender = $importedContact->gender;
            $contact->first_name = $importedContact->first_name;
            $contact->last_name = $importedContact->last_name;
            $contact->email = $importedContact->email;
            $contact->phone = $importedContact->phone;
            $contact->mobile = $importedContact->phone_2;
            $contact->job_title = $importedContact->job_title;
            $contact->contact_name = $importedContact->contact_name;

            $contact->updateTimestamps();
        });
    }
}
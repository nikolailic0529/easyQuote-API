<?php

namespace App\Services\Contact;

use App\Models\Contact;
use App\Models\ImportedContact;

class ContactHashResolver
{
    public function __invoke(Contact|ImportedContact $contact): string
    {
        return md5(implode('~', [
            $contact->first_name,
            $contact->last_name,
            $contact->email,
            $contact->phone,
            $contact->job_title,
        ]));
    }
}
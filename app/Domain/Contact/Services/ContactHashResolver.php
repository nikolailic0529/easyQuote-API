<?php

namespace App\Domain\Contact\Services;

use App\Domain\Contact\Models\Contact;
use App\Domain\Contact\Models\ImportedContact;

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

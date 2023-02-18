<?php

namespace App\Domain\Contact\Enum;

use App\Foundation\Support\Enum\Enum;

final class ContactType extends Enum
{
    const HARDWARE = 'Hardware';
    const SOFTWARE = 'Software';
    const INVOICE = 'Invoice';
}

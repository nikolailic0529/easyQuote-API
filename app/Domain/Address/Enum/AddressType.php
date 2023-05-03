<?php

namespace App\Domain\Address\Enum;

final class AddressType extends \App\Foundation\Support\Enum\Enum
{
    const INVOICE = 'Invoice';
    const CLIENT = 'Client';
    const MACHINE = 'Machine';
    const EQUIPMENT = 'Equipment';
    const HARDWARE = 'Hardware';
    const SOFTWARE = 'Software';
}

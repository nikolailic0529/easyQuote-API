<?php

namespace App\Enum;

final class AddressType extends Enum
{
    const
        INVOICE = 'Invoice',
        CLIENT = 'Client',
        MACHINE = 'Machine',
        EQUIPMENT = 'Equipment',
        HARDWARE = 'Hardware',
        SOFTWARE = 'Software';
}

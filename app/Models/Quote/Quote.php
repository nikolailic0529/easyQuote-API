<?php

namespace App\Models\Quote;

use App\Contracts\Multitenantable;
use App\Traits\{
    Activatable,
    Migratable,
    NotifiableModel,
    Quote\HasQuoteVersions,
    Quote\HasContract,
    Submittable
};

class Quote extends BaseQuote implements Multitenantable
{
    use HasQuoteVersions, HasContract, NotifiableModel, Submittable, Activatable, Migratable;
}

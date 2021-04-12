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
use App\Models\Customer\Customer;

/**
 * @property Customer|null $customer
 * @property string|null $submitted_at
 */
class Quote extends BaseQuote implements Multitenantable
{
    use HasQuoteVersions, HasContract, NotifiableModel, Submittable, Activatable, Migratable;
}

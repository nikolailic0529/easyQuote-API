<?php

namespace App\Models\Quote;

use App\Contracts\Multitenantable;
use App\Models\Attachment;
use App\Models\Customer\Customer;
use App\Traits\{Activatable, Migratable, NotifiableModel, Quote\HasContract, Quote\HasQuoteVersions, Submittable};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property Customer|null $customer
 * @property string|null $submitted_at
 * @property string|null $assets_migrated_at
 *
 * @property-read Collection<Attachment>|Attachment[] $attachments
 */
class Quote extends BaseQuote implements Multitenantable
{
    use HasQuoteVersions, HasContract, NotifiableModel, Submittable, Activatable, Migratable;

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(
            related: Attachment::class,
            name: 'attachable',
            relatedPivotKey: 'attachment_id'
        );
    }
}

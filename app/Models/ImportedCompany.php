<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string|null $company_name
 * @property string|null $company_category
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $vendors_cs
 * @property string|null $vat
 * @property string|null $vat_type
 * @property int|null $flags
 *
 * @property-read Collection<int, ImportedAddress>|ImportedAddress[] $addresses
 * @property-read Collection<int, ImportedContact>|ImportedContact[] $contacts
 * @property-read ImportedContact|null $primaryContact
 *
 */
class ImportedCompany extends Model
{
    use Uuid;

    const IS_RESELLER = 1 << 0;
    const IS_END_USER = 1 << 1;

    protected $guarded = [];

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(ImportedAddress::class, table: 'imported_address_imported_company');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(ImportedContact::class, table: 'imported_contact_imported_company');
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(ImportedContact::class);
    }

    public function getFlag(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }
}

<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property mixed|string $company_name
 * @property mixed|string $company_category
 * @property mixed|null $email
 * @property mixed|null $phone
 * @property mixed|null $website
 *
 * @property-read Collection<int, ImportedAddress>|ImportedAddress[] $addresses
 * @property-read Collection<int, ImportedContact>|ImportedContact[] $contacts
 *
 */
class ImportedCompany extends Model
{
    use Uuid;

    protected $guarded = [];

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(ImportedAddress::class, table: 'imported_address_imported_company');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(ImportedContact::class, table: 'imported_contact_imported_company');
    }
}

<?php

namespace App\Models;

use App\Contracts\HasOwner;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string|null $pl_reference
 * @property string|null $company_name
 * @property string|null $customer_type
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $address_1
 * @property string|null $address_2
 * @property string|null $city
 * @property string|null $post_code
 * @property string|null $state
 * @property string|null $state_code
 * @property string|null $country_name
 * @property string|null $hw_country_code
 * @property string|null $sw_country_code
 * @property string|null $vendors_cs
 * @property string|null $vat
 * @property string|null $vat_type
 * @property int|null $flags
 * @property string|null $picture_url
 * @property string|null $picture_filename
 * @property ArrayObject $contact_relations
 * @property \Illuminate\Support\Collection $company_categories
 *
 * @property-read SalesUnit|null $salesUnit
 * @property-read Collection<int, ImportedAddress>|ImportedAddress[] $addresses
 * @property-read Collection<int, ImportedContact>|ImportedContact[] $contacts
 * @property-read ImportedContact|null $primaryContact
 *
 */
class ImportedCompany extends Model implements HasOwner
{
    use Uuid;

    const IS_RESELLER = 1 << 0;
    const IS_END_USER = 1 << 1;

    protected $guarded = [];

    protected $casts = [
        'company_categories' => AsCollection::class,
        'contact_relations' => AsArrayObject::class,
    ];

    public function getFlag(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(SalesUnit::class);
    }

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
}

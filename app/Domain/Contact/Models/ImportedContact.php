<?php

namespace App\Domain\Contact\Models;

use App\Domain\Address\Models\ImportedAddress;
use App\Domain\Contact\Enum\GenderEnum;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Contracts\HasOwner;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null                              $pl_reference
 * @property mixed|string                             $contact_type
 * @property \App\Domain\Contact\Enum\GenderEnum|null $gender
 * @property mixed|null                               $first_name
 * @property mixed|null                               $last_name
 * @property mixed|null                               $email
 * @property mixed|null                               $phone
 * @property mixed|null                               $phone_2
 * @property mixed|null                               $job_title
 * @property false|mixed                              $is_verified
 * @property mixed|string                             $contact_name
 * @property SalesUnit|null                           $salesUnit
 */
class ImportedContact extends Model implements HasOwner
{
    use Uuid;

    public bool $is_primary = false;

    protected $guarded = [];

    protected $casts = [
        'gender' => GenderEnum::class,
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(SalesUnit::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(ImportedAddress::class);
    }
}

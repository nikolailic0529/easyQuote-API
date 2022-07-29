<?php

namespace App\Models;

use App\Contracts\HasOwner;
use App\Enum\GenderEnum;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $pl_reference
 * @property mixed|string $contact_type
 * @property GenderEnum|null $gender
 * @property mixed|null $first_name
 * @property mixed|null $last_name
 * @property mixed|null $email
 * @property mixed|null $phone
 * @property mixed|null $phone_2
 * @property mixed|null $job_title
 * @property false|mixed $is_verified
 * @property mixed|string $contact_name
 *
 * @property-read SalesUnit|null $salesUnit
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

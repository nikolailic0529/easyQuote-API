<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CompanyNote
 *
 * @property string|null $company_id
 * @property string|null $user_id
 * @property string|null $text
 *
 * @property-read User|null $user
 * @property-read Company|null $company
 */
class CompanyNote extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}

<?php

namespace App\Domain\HpeContract\Models;

use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class HpeContractFile.
 *
 * @property string|null $id
 * @property string|null $user_id
 * @property string|null $original_file_name
 * @property string|null $original_file_path
 * @property string|null $date_format
 * @property string|null $imported_at
 */
class HpeContractFile extends Model
{
    use Uuid;
    use SoftDeletes;
    use Multitenantable;

    protected $guarded = [];

    public function hpeContractData(): HasMany
    {
        return $this->hasMany(HpeContractData::class);
    }
}

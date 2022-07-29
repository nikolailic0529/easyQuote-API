<?php

namespace App\Models;

use App\Enum\AttachmentType;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $pl_reference
 * @property string|null $filepath
 * @property string|null $filename
 * @property string|null $extension
 * @property int|null $size
 * @property AttachmentType|null $type
 *
 * @property-read Collection<int, Attachable> $attachables
 */
class Attachment extends Model
{
    use Uuid, SoftDeletes;

    protected $fillable = [
        'type', 'filepath', 'filename', 'extension', 'size',
    ];

    protected $casts = [
        'type' => AttachmentType::class,
    ];

    public function attachables(): HasMany
    {
        return $this->hasMany(Attachable::class);
    }
}

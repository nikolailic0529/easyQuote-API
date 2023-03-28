<?php

namespace App\Domain\Attachment\Models;

use App\Domain\Attachment\Enum\AttachmentType;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Contracts\HasOwner;
use App\Domain\User\Models\User;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null                       $pl_reference
 * @property string|null                       $filepath
 * @property string|null                       $filename
 * @property string|null                       $md5_hash
 * @property string|null                       $extension
 * @property int|null                          $size
 * @property AttachmentType|null               $type
 * @property int|null                          $flags
 * @property \App\Domain\User\Models\User|null $owner
 * @property Collection<int, Attachable>       $attachables
 */
class Attachment extends Model implements HasOwner
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

    const IS_DELETE_PROTECTED = 1 << 0;

    protected $fillable = [
        'type', 'filepath', 'filename', 'extension', 'size',
    ];

    protected $casts = [
        'type' => AttachmentType::class,
    ];

    protected static function newFactory(): AttachmentFactory
    {
        return AttachmentFactory::new();
    }

    public function getFlag(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachables(): HasMany
    {
        return $this->hasMany(Attachable::class);
    }
}

<?php

namespace App\Models\System;

use App\Traits\{
    Uuid,
    BelongsToUser,
};
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
    Relations\MorphTo,
};

/**
 * @property int|null $priority
 * @property string|null $url
 * @property string|null $message
 */
class Notification extends Model
{
    use Uuid, BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id', 'url', 'message', 'subject_type', 'subject_id', 'read_at'
    ];

    protected $hidden = ['deleted_at'];

    protected $observables = ['read'];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     *
     * @return void
     */
    public function markAsRead(): bool
    {
        if (is_null($this->read_at)) {
            $pass = $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
            $this->fireModelEvent('read');
            return $pass;
        }

        return false;
    }

    /**
     * Mark the notification as unread.
     *
     * @return void
     */
    public function markAsUnread(): bool
    {
        if (!is_null($this->read_at)) {
            return $this->forceFill(['read_at' => null])->save();
        }

        return false;
    }

    public function getReadAttribute(): bool
    {
        return !is_null($this->read_at);
    }

    public function getUnreadAttribute(): bool
    {
        return !$this->read;
    }

    public function getPriorityAttribute($value): string
    {
        return __('priority.' . $value);
    }
}

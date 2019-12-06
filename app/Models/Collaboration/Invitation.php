<?php

namespace App\Models\Collaboration;

use App\Models\UuidModel;
use App\Traits\{
    BelongsToUser,
    BelongsToRole,
    CanGenerateToken,
    Expirable,
    Search\Searchable
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Invitation extends UuidModel
{
    use BelongsToUser, BelongsToRole, SoftDeletes, Searchable, CanGenerateToken, Expirable, LogsActivity;

    protected $fillable = [
        'email', 'user_id', 'role_id', 'host'
    ];

    protected $hidden = [
        'user', 'role', 'updated_at', 'deleted_at'
    ];

    protected $appends = [
        'user_email', 'role_name', 'url'
    ];

    protected $observables = [
        'resended', 'canceled'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static $recordEvents = ['created', 'deleted'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->attributes['expires_at'] = now()->addDay()->toDateTimeString();
        });

        /**
         * Generating a new Invitation Token.
         */
        static::generated(function ($model) {
            $model->attributes['invitation_token'] = $model->generateToken();
        });
    }

    /**
     * Get Url with Invitation Token.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return "{$this->host}/signup/{$this->attributes['invitation_token']}";
    }

    public function getUserEmailAttribute()
    {
        return $this->user->email;
    }

    public function getRoleNameAttribute()
    {
        return $this->role->name;
    }

    public function getRouteKeyName()
    {
        return 'invitation_token';
    }

    public function resend()
    {
        $this->fireModelEvent('resended', false);

        return $this->forceFill([
            'expires_at' => now()->addDay()->toDateTimeString()
        ])->save();
    }

    public function cancel()
    {
        $this->fireModelEvent('canceled', false);

        return $this->forceFill([
            'expires_at' => null
        ])->save();
    }
}

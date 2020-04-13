<?php

namespace App\Models\Collaboration;

use App\Traits\{
    BelongsToUser,
    BelongsToRole,
    CanGenerateToken,
    Expirable,
    Search\Searchable,
    Activity\LogsActivity,
    Auth\Multitenantable,
    Uuid
};
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
};
use Illuminate\Support\Str;

class Invitation extends Model
{
    use Uuid,
        Multitenantable,
        BelongsToUser,
        BelongsToRole,
        SoftDeletes,
        Searchable,
        CanGenerateToken,
        Expirable,
        LogsActivity,
        SoftDeletes;

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

    protected static $submitEmptyLogs = true;

    protected static $recordEvents = ['created', 'deleted'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->attributes['expires_at'] = now()->addDay()->toDateTimeString();
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
        $baseUrl = (string) Str::of($this->host)->finish('/')->finish('signup/');

        return "{$baseUrl}{$this->invitation_token}";
    }

    public function getUserEmailAttribute()
    {
        return $this->user->email ?? null;
    }

    public function getRoleNameAttribute()
    {
        return $this->role->name ?? null;
    }

    public function getRouteKeyName()
    {
        return 'invitation_token';
    }

    public function resend()
    {
        $this->fireModelEvent('resended', false);

        return $this->forceFill(['expires_at' => now()->addDay()])->save();
    }

    public function cancel()
    {
        $this->fireModelEvent('canceled', false);

        return $this->forceFill(['expires_at' => null])->save();
    }

    public function getItemNameAttribute()
    {
        return "Invitation ({$this->email})";
    }
}

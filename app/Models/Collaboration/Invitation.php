<?php namespace App\Models\Collaboration;

use App\Models\UuidModel;
use App\Traits \ {
    BelongsToUser,
    BelongsToRole,
    Search\Searchable
};
use Illuminate\Database\Eloquent\SoftDeletes;

class Invitation extends UuidModel
{
    use BelongsToUser, BelongsToRole, SoftDeletes, Searchable;

    protected $fillable = [
        'email', 'user_id', 'role_id', 'host', 'expires_at'
    ];

    protected $hidden = [
        'user', 'role', 'updated_at', 'deleted_at'
    ];

    protected $appends = [
        'user_email', 'role_name', 'url', 'is_expired'
    ];

    protected $observables = [
        'resended', 'canceled'
    ];

    protected $dates = [
        'expires_at'
    ];

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
     * @return mixed
     */
    public function getUrlAttribute()
    {
        $invitation_token = $this->attributes['invitation_token'];

        return "{$this->host}/#/signup/{$invitation_token}";
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

    public function generateToken(): string
    {
        return substr(md5($this->attributes['id'] . $this->attributes['email'] . time()), 0, 32);
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

    public function scopeExpired($query)
    {
        return $query->whereNull('expires_at')
            ->orWhere('expires_at', '<', now()->toDateTimeString())
            ->limit(999999999);
    }

    public function scopeNonExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '>', now()->toDateTimeString())
            ->limit(999999999);
    }

    public function getIsExpiredAttribute()
    {
        return is_null($this->expires_at) || $this->expires_at->lt(now());
    }
}

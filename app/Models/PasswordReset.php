<?php

namespace App\Models;

use App\Traits\{
    BelongsToUser,
    Expirable,
    CanGenerateToken
};

class PasswordReset extends BaseModel
{
    use CanGenerateToken, Expirable, BelongsToUser;

    protected $fillable = [
        'user_id', 'email', 'token', 'host', 'expires_at'
    ];

    protected $appends = [
        'url'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->attributes['expires_at'] = now()->addHours(12)->toDateTimeString();
        });

        /**
         * Generating a new Invitation Token.
         */
        static::generated(function ($model) {
            $model->attributes['token'] = $model->generateToken();
        });
    }

    /**
     * Get Url with Password Reset Request Token.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return "{$this->host}/reset/{$this->attributes['token']}";
    }

    public function getRouteKeyName()
    {
        return 'token';
    }

    public function cancel()
    {
        return $this->forceFill([
            'expires_at' => null
        ])->save();
    }
}

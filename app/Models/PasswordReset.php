<?php

namespace App\Models;

use App\Traits\{
    BelongsToUser,
    Expirable,
    CanGenerateToken,
    Uuid
};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PasswordReset extends Model
{
    use Uuid, CanGenerateToken, Expirable, BelongsToUser;

    protected $fillable = [
        'user_id', 'email', 'token', 'host', 'expires_at'
    ];

    protected $appends = [
        'url'
    ];

    protected static function booted()
    {
        static::creating(function (PasswordReset $model) {
            if (!isset($model->attributes['expires_at'])) {
                $model->attributes['expires_at'] = now()->addHours(12)->toDateTimeString();
            }

            if (!isset($model->attributes['token'])) {
                $model->attributes['token'] = $model->generateToken();
            }
        });
    }

    /**
     * Get Url with Password Reset Request Token.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return Str::of(config('app.ui_url'))->finish('/')->append('reset/', $this->token);
    }

    public function getRouteKeyName()
    {
        return 'token';
    }

    public function cancel(): bool
    {
        return $this->forceFill(['expires_at' => null])->saveOrFail();
    }
}

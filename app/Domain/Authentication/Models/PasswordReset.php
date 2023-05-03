<?php

namespace App\Domain\Authentication\Models;

use App\Domain\Authentication\Concerns\Expirable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PasswordReset extends Model
{
    use Uuid;
    use Expirable;
    use BelongsToUser;

    protected $fillable = [
        'user_id', 'email', 'token', 'host', 'expires_at',
    ];

    protected $appends = [
        'url',
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

    public function generateToken(): string
    {
        $key = app()['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', Str::random(40), $key);
    }

    /**
     * Get Url with Password Reset Request Token.
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

<?php

namespace App\Domain\Authentication\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\PersonalAccessTokenResult;

class AccessAttempt extends Model
{
    use Uuid;

    public bool $previouslyKnown = false;

    protected $fillable = [
        'email', 'ip_address', 'local_ip',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setDetails();
        });
    }

    public function markAsSuccessful(?PersonalAccessTokenResult $token = null)
    {
        return $this->forceFill([
            'is_success' => true,
            'token' => optional($token)->accessToken,
        ])->save();
    }

    public function setDetails(): void
    {
        $this->forceFill(['user_agent' => request()->userAgent()]);
    }

    public function markAsPreviouslyKnown(): void
    {
        $this->previouslyKnown = true;
    }

    public function getIpAddressAttribute($value): string
    {
        return $value ?? \Str::random(20);
    }

    public function getIpAttribute()
    {
        return $this->ip_address;
    }

    public function setLocalIpAttribute(string $value)
    {
        $this->attributes['ip_address'] = $value;
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('is_success', true);
    }

    public function scopeEmail(Builder $query, string $email): Builder
    {
        return $query->whereEmail($email);
    }

    public function scopeIp(Builder $query, string $ipAddress): Builder
    {
        return $query->whereIpAddress($ipAddress);
    }
}

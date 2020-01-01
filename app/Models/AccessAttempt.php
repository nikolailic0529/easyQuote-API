<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\PersonalAccessTokenResult;
use Str;

class AccessAttempt extends BaseModel
{
    protected $fillable = [
        'email', 'ip_address', 'local_ip'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->setDetails();
        });
    }

    public function markAsSuccessful(PersonalAccessTokenResult $token)
    {
        return $this->forceFill([
            'is_success' => true,
            'token' => $token->accessToken
        ])->save();
    }

    public function setDetails()
    {
        $this->forceFill([
            'user_agent' => request()->userAgent()
        ]);
    }

    public function getIpAddressAttribute($value): string
    {
        return $value ?? Str::random(20);
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

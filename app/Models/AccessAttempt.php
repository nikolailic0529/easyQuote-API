<?php namespace App\Models;

use App\Models\UuidModel;
use Laravel\Passport\PersonalAccessTokenResult;

class AccessAttempt extends UuidModel
{
    protected $fillable = [
        'email'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
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
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

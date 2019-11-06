<?php namespace App\Models\Collaboration;

use App\Models\UuidModel;
use App\Traits \ {
    BelongsToUser,
    BelongsToRole
};

class Invitation extends UuidModel
{
    use BelongsToUser, BelongsToRole;

    protected $fillable = [
        'email', 'user_id', 'role_id', 'host'
    ];

    protected $appends = [
        'user_email', 'role_name', 'url'
    ];

    protected static function boot()
    {
        parent::boot();

        /**
         * Generating a new Invitation Token.
         */
        static::generated(function ($model) {
            $model->attributes['invitation_token'] = substr(md5($model->attributes['id'] . $model->attributes['email']), 0, 32);
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
}

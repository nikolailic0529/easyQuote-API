<?php

namespace App\Models\System;

use App\Models\BaseModel;
use App\Models\User;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends BaseModel
{
    use BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id', 'url', 'message', 'subject_type', 'subject_id'
    ];

    protected $hidden = ['deleted_at'];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function withSubject(Model $model): self
    {
        return $this->subject()->associate($model);
    }

    public function message(string $message): self
    {
        $this->attributes[__FUNCTION__] = $message;

        return $this;
    }

    public function for(User $user): self
    {
        return $this->user()->associate($user);
    }

    public function url(string $url): self
    {
        $this->attributes[__FUNCTION__] = $url;

        return $this;
    }

    public function priority(int $priority): self
    {
        $this->attributes[__FUNCTION__] = $priority;

        return $this;
    }

    public function store(array $options = []): bool
    {
        if (app()->runningUnitTests()) {
            return false;
        }

        return $this->save($options);
    }
}

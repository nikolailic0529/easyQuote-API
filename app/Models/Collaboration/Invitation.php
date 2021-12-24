<?php

namespace App\Models\Collaboration;

use App\Contracts\SearchableEntity;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Traits\{Activity\LogsActivity, Auth\Multitenantable, CanGenerateToken, Search\Searchable, Uuid};
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo, SoftDeletes};
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class Invitation
 *
 * @property string|null $email
 * @property string|null $user_id
 * @property string|null $role_id
 * @property string|null $team_id
 * @property string|null $host
 * @property Carbon|null $expires_at
 *
 * @property-read User|null $user
 * @property-read Role|null $role
 */
class Invitation extends Model implements SearchableEntity
{
    use Uuid,
        Multitenantable,
        SoftDeletes,
        Searchable,
        CanGenerateToken,
        LogsActivity,
        SoftDeletes,
        EloquentJoin;

    protected $fillable = [
        'email', 'user_id', 'role_id', 'host', 'expires_at',
    ];

    protected $hidden = [
        'user', 'role', 'updated_at', 'deleted_at',
    ];

    protected $appends = [
        'user_email', 'role_name', 'url', 'is_expired',
    ];

    protected $observables = [
        'resended', 'canceled',
    ];

    protected $dates = [
        'expires_at',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = true;

    protected static $recordEvents = ['created', 'deleted'];

    protected static function booted()
    {
        static::creating(function (Invitation $model) {
            if (!isset($model->attributes['expires_at'])) {
                $model->attributes['expires_at'] = now()->addDay()->toDateTimeString();
            }

            if (!isset($model->attributes['invitation_token'])) {
                $model->attributes['invitation_token'] = $model->generateToken();
            }
        });
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get Url with Invitation Token.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        return (string)Str::of($this->host)->finish('/')->finish('signup/')->append($this->invitation_token);
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

    public function toSearchArray(): array
    {
        return [
            'role_name' => optional($this->role)->name,
            'email' => $this->email,
            'created_at' => optional($this->created_at)->format(config('date.format')),
            'expires_at' => optional($this->expires_at)->format(config('date.format')),
        ];
    }

    public function getItemNameAttribute()
    {
        return "Invitation ({$this->email})";
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNull('expires_at')
            ->orWhere('expires_at', '<', now())
            ->limit(999999999);
    }

    public function scopeNonExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->limit(999999999);
    }

    public function getIsExpiredAttribute(): bool
    {
        return is_null($this->expires_at) || $this->expires_at->lt(now());
    }
}

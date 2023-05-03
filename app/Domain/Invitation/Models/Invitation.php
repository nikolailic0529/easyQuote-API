<?php

namespace App\Domain\Invitation\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Models\ModelHasCompanies;
use App\Domain\SalesUnit\Models\ModelHasSalesUnits;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Team\Models\Team;
use App\Domain\User\Models\User;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Database\Factories\InvitationFactory;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class Invitation.
 *
 * @property string|null                                         $email
 * @property string|null                                         $user_id
 * @property string|null                                         $role_id
 * @property string|null                                         $team_id
 * @property string|null                                         $host
 * @property Carbon|null                                         $expires_at
 * @property User|null                                           $user
 * @property Role|null                                           $role
 * @property Collection<int, SalesUnit>                          $salesUnits
 * @property Collection<int, \App\Domain\Company\Models\Company> $companies
 */
class Invitation extends Model implements SearchableEntity
{
    use Uuid;
    use Multitenantable;
    use SoftDeletes;
    use Searchable;
    use LogsActivity;
    use SoftDeletes;
    use EloquentJoin;
    use HasFactory;

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

    protected static function newFactory(): InvitationFactory
    {
        return InvitationFactory::new();
    }

    public function generateToken(): string
    {
        $key = app()['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', Str::random(40), $key);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Authorization\Models\Role::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function salesUnits(): MorphToMany
    {
        return $this->morphToMany(
            related: SalesUnit::class,
            name: 'model',
            table: (new ModelHasSalesUnits())->getTable()
        )
            ->using(\App\Domain\SalesUnit\Models\ModelHasSalesUnits::class);
    }

    public function companies(): MorphToMany
    {
        return $this->morphToMany(
            related: Company::class,
            name: 'model',
            table: (new ModelHasCompanies())->getTable()
        )
            ->using(ModelHasCompanies::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get Url with Invitation Token.
     */
    public function getUrlAttribute(): string
    {
        return (string) Str::of(config('app.ui_url'))
            ->finish('/')
            ->append('signup', '/', $this->invitation_token);
    }

    public function getUserEmailAttribute()
    {
        return $this->user->email ?? null;
    }

    public function getRoleNameAttribute()
    {
        return $this->role->name ?? null;
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
            'role_name' => $this->role?->name,
            'email' => $this->email,
            'issuer_fullname' => $this->user?->user_fullname,
            'created_at' => $this->created_at?->format(config('date.format')),
            'expires_at' => $this->expires_at?->format(config('date.format')),
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

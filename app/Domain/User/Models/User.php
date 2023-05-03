<?php

namespace App\Domain\User\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Activity\Models\Activity;
use App\Domain\Authorization\Concerns\HasModulePermissions;
use App\Domain\Authorization\Concerns\HasPermissionTargets;
use App\Domain\Authorization\Facades\Permission;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Concerns\BelongsToCompany;
use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Discount\Concerns\HasDiscounts;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Image\Contracts\HasImagesDirectory;
use App\Domain\Invitation\Concerns\HasInvitations;
use App\Domain\Invitation\Models\Invitation;
use App\Domain\Margin\Concerns\HasCountryMargins;
use App\Domain\Notification\DataTransferObjects\NotificationSettings\NotificationSettingsData;
use App\Domain\QuoteFile\Concerns\HasImportableColumns;
use App\Domain\QuoteFile\Concerns\HasQuoteFiles;
use App\Domain\QuoteFile\Concerns\HasQuoteFilesDirectory;
use App\Domain\Rescue\Concerns\HasQuotes;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\HasApiTokens;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Domain\Shared\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\Template\Concerns\HasQuoteTemplates;
use App\Domain\Template\Concerns\HasTemplateFields;
use App\Domain\Timezone\Concerns\BelongsToTimezone;
use App\Domain\Timezone\Models\Timezone;
use App\Domain\User\Concerns\EnforceableChangePassword;
use App\Domain\User\Concerns\Loginable;
use App\Domain\User\Concerns\Notifiable;
use App\Domain\User\Concerns\PerformsActivity;
use App\Domain\User\Enum\UserLanguageEnum;
use App\Domain\Vendor\Concerns\HasVendors;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Database\Factories\UserFactory;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string|null                                                                $pl_reference
 * @property string|null                                                                $team_id
 * @property string|null                                                                $first_name
 * @property string|null                                                                $middle_name
 * @property string|null                                                                $last_name
 * @property string|null                                                                $email
 * @property string|null                                                                $timezone_id
 * @property string|null                                                                $password
 * @property string|null                                                                $phone
 * @property string|null                                                                $language
 * @property int|null                                                                   $failed_attempts
 * @property mixed                                                                      $activated_at
 * @property string|null                                                                $user_fullname
 * @property \App\Domain\Team\Models\Team|null                                          $team
 * @property Collection<int, \App\Domain\SalesUnit\Models\SalesUnit>                    $salesUnits
 * @property Collection<int, Company>                                                   $companies
 * @property Collection<int, \App\Domain\Authorization\Facades\Permission>|Permission[] $permissions
 * @property Timezone                                                                   $timezone
 * @property \App\Domain\Image\Models\Image|null                                        $image
 * @property \App\Domain\Image\Models\Image|null                                        $picture
 * @property Collection<int, User>                                                      $ledTeamUsers
 * @property Collection<int, \App\Domain\Authorization\Models\Role>                     $roles
 * @property Collection<int, \App\Domain\Team\Models\Team>                              $ledTeams
 * @property Collection<int, \App\Domain\SalesUnit\Models\SalesUnit>                    $salesUnitsFromLedTeams
 * @property Country                                                                    $country
 * @property \App\Domain\Activity\Models\Activity|null                                  $latestLogin
 * @property NotificationSettingsData                                                   $notification_settings
 * @property Role                                                                       $role
 */
class User extends Model implements ActivatableInterface, AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, HasImagesDirectory, SearchableEntity, ProvidesIdForHumans
{
    use Uuid;
    use Authenticatable;
    use Authorizable;
    use MustVerifyEmail;
    use CanResetPassword;
    use HasRoles;
    use HasPermissionTargets;
    use HasModulePermissions;
    use HasImportableColumns;
    use HasQuotes;
    use HasQuoteFiles;
    use HasQuoteFilesDirectory;
    use HasApiTokens;
    use HasInvitations;
    use BelongsToTimezone;
    use BelongsToCompany;
    use HasCountryMargins;
    use HasDiscounts;
    use HasVendors;
    use HasQuoteTemplates;
    use HasTemplateFields;
    use Activatable;
    use Searchable;
    use SoftDeletes;
    use LogsActivity;
    use Loginable;
    use PerformsActivity;
    use EnforceableChangePassword;
    use HasRelationships;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'role_id',
        'team_id',
        'timezone_id',
        'country_id',
        'company_id',
        'hpe_contract_template_id',
        'email',
        'password',
        'phone',
        'default_route',
        'recent_notifications_limit',
        'failed_attempts',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'roles', 'updated_at', 'deleted_at', 'image', 'notification_settings',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'notification_settings' => NotificationSettingsData::class,
        'language' => UserLanguageEnum::class,
    ];

    protected static $logAttributes = [
        'first_name', 'middle_name', 'last_name', 'email', 'phone', 'role.name', 'team.name', 'timezone.text',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function guardName(): string
    {
        return 'api';
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Team\Models\Team::class);
    }

    public function ledTeams(): BelongsToMany
    {
        return $this->belongsToMany(\App\Domain\Team\Models\Team::class, 'team_team_leader', 'team_leader_id');
    }

    public function ledTeamUsers(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->ledTeams(), (new \App\Domain\Team\Models\Team())->users());
    }

    public function latestLogin(): MorphOne
    {
        $related = new Activity();

        return $this->morphOne($related::class, 'causer')
            ->where($related->qualifyColumn('description'), 'authenticated')
            ->ofMany([$related->getCreatedAtColumn() => 'MAX'], static function (Builder $subQuery): void {
                $subQuery->where($subQuery->qualifyColumn('description'), 'authenticated');
            });
    }

    public function salesUnits(): MorphToMany
    {
        return $this->morphToMany(
            related: \App\Domain\SalesUnit\Models\SalesUnit::class,
            name: 'model',
            table: (new \App\Domain\SalesUnit\Models\ModelHasSalesUnits())->getTable()
        )
            ->using(\App\Domain\SalesUnit\Models\ModelHasSalesUnits::class);
    }

    public function salesUnitsFromLedTeams(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->ledTeams(),
            (new \App\Domain\Team\Models\Team())->salesUnits(),
        );
    }

    public function companies(): MorphToMany
    {
        return $this->morphToMany(
            related: Company::class,
            name: 'model',
            table: (new \App\Domain\Company\Models\ModelHasCompanies())->getTable()
        )
            ->using(\App\Domain\Company\Models\ModelHasCompanies::class);
    }

    public function sharedModelRelations(): HasMany
    {
        return $this->hasMany(ModelHasSharingUsers::class);
    }

    public function companiesThroughRoles(): HasManyDeep
    {
        return $this->hasManyDeep(
            Company::class,
            [
                'model_has_roles', \App\Domain\Authorization\Models\Role::class,
                \App\Domain\Authorization\Models\ModelHasRoles::class.' as model_roles',
            ],
            [['model_type', 'model_id'], 'id', 'role_id', 'id'],
            ['id', 'role_id', 'id', ['model_type', 'model_id']],
        );
    }

    public function hpeContractTemplate(): BelongsTo
    {
        return $this->belongsTo(HpeContractTemplate::class)->withDefault();
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeAdministrators(Builder $query): Builder
    {
        return $query->role('Administrator');
    }

    public function scopeNonAdministrators(Builder $query): Builder
    {
        return $query->whereDoesntHave('roles', static fn ($query) => $query->whereName('Administrator'));
    }

    public function scopeEmail(Builder $query, string $email): Builder
    {
        return $query->whereEmail($email);
    }

    public function interact($model): void
    {
        if ($model instanceof Invitation) {
            $this->assignRole($model->role);
            $model->delete();
        }
    }

    public function getRoleAttribute()
    {
        return $this->roles->first(null, \App\Domain\Authorization\Models\Role::make());
    }

    public function getRoleIdAttribute()
    {
        return $this->role->id;
    }

    public function getRoleNameAttribute()
    {
        return $this->role->name;
    }

    public function getTimezoneTextAttribute()
    {
        return $this->timezone->text;
    }

    public function imagesDirectory(): string
    {
        return 'images/users';
    }

    public function toSearchArray(): array
    {
        return [
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'role_name' => $this->role?->name,
            'team_name' => $this->team?->team_name,
            'unit_names' => $this->salesUnits->pluck('unit_name')->all(),
            'country_code' => $this->country?->iso_3166_2,
            'country_name' => $this->country?->name,
            'language' => $this->language,
        ];
    }

    public function getItemNameAttribute()
    {
        return $this->email;
    }

    public function grantedModuleLevel(string $module)
    {
        return Permission::grantedModuleLevel($module, $this);
    }

    public function withAppends(...$attributes)
    {
        $appends = ['role_id', 'role_name', 'picture', 'privileges', 'role_properties', 'must_change_password'];

        return $this->append(array_merge($appends, $attributes));
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Image\Models\Image::class, 'picture_id');
    }

    public function getPictureAttribute(): ?string
    {
        if (null === $this->image) {
            return null;
        }

        return asset('storage/'.$this->image->original_image);
    }

    public function isActive(): bool
    {
        return null !== $this->activated_at;
    }

    public function getIdForHumans(): string
    {
        return $this->email;
    }
}

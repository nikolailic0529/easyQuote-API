<?php

namespace App\Domain\Company\Models;

use App\Domain\Address\Concerns\BelongsToAddresses;
use App\Domain\Address\Models\Address;
use App\Domain\Appointment\Contracts\HasOwnAppointments;
use App\Domain\Appointment\Contracts\LinkedToAppointments;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Models\ModelHasAppointments;
use App\Domain\Asset\Models\Asset;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Company\Builders\CompanyBuilder;
use App\Domain\Company\Enum\CompanyStatusEnum;
use App\Domain\Company\Enum\CustomerTypeEnum;
use App\Domain\Contact\Concerns\BelongsToContacts;
use App\Domain\Country\Models\Country;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Image\Contracts\HasImagesDirectory;
use App\Domain\Image\Contracts\WithLogo;
use App\Domain\Image\Services\ThumbHelper;
use App\Domain\Note\Contracts\HasOwnNotes;
use App\Domain\Note\Models\ModelHasNotes;
use App\Domain\Note\Models\Note;
use App\Domain\Pipeliner\Models\PipelinerSyncError;
use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Rescue\Quote\HasQuotes;
use App\Domain\SalesUnit\Contracts\HasSalesUnit;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\HasTimestamps;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Domain\Shared\Eloquent\Contracts\HasOrderedScope;
use App\Domain\Shared\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\Shared\SharingUser\Contacts\HasSharingUserRelations;
use App\Domain\Stats\Models\CustomerTotal;
use App\Domain\Task\Contracts\LinkedToTasks;
use App\Domain\Task\Models\Task;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\User\Contracts\HasOwner;
use App\Domain\User\Models\ModelHasSharingUsers;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Concerns\BelongsToVendors;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Carbon\CarbonInterface;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * Class Company.
 *
 * @property string|null                                                                                          $pl_reference
 * @property string|null                                                                                          $user_id
 * @property string|null                                                                                          $default_country_id
 * @property string|null                                                                                          $default_vendor_id
 * @property string|null                                                                                          $default_template_id
 * @property string|null                                                                                          $registered_number
 * @property string|null                                                                                          $name
 * @property string|null                                                                                          $short_code
 * @property string|null                                                                                          $vs_company_code
 * @property string|null                                                                                          $type
 * @property string|null                                                                                          $source
 * @property \App\Domain\Company\Enum\CustomerTypeEnum|null                                                       $customer_type
 * @property string|null                                                                                          $email
 * @property string|null                                                                                          $vat
 * @property string|null                                                                                          $vat_type
 * @property string|null                                                                                          $phone
 * @property string|null                                                                                          $website
 * @property int|null                                                                                             $employees_number
 * @property CarbonInterface|null                                                                                 $creation_date
 * @property string|null                                                                                          $activated_at
 * @property int|null                                                                                             $flags
 * @property \App\Domain\Company\Enum\CompanyStatusEnum                                                           $status
 * @property \App\Domain\Image\Models\Image|null                                                                  $image
 * @property Collection<int, User>|User[]                                                                         $sharingUsers
 * @property Collection<int, ModelHasSharingUsers>|ModelHasSharingUsers[]                                         $sharingUserRelations
 * @property Collection<int, Address>|Address[]                                                                   $addresses
 * @property Collection<int, \App\Domain\Contact\Models\Contact>|\App\Domain\Contact\Models\Contact[]             $contacts
 * @property Collection<int, \App\Domain\Worldwide\Models\Opportunity>|\App\Domain\Worldwide\Models\Opportunity[] $opportunities
 * @property Collection<int, \App\Domain\Worldwide\Models\Opportunity>|\App\Domain\Worldwide\Models\Opportunity[] $opportunitiesWhereEndUser
 * @property Collection<int, WorldwideQuote>|WorldwideQuote[]                                                     $worldwideQuotes
 * @property Collection<int, Task>|Task[]                                                                         $tasks
 * @property Collection<int, \App\Domain\Appointment\Models\Appointment>|Appointment[]                            $ownAppointments
 * @property Collection<int, Note>|Note[]                                                                         $notes
 * @property Collection<int, Attachment>|Attachment[]                                                             $attachments
 * @property Collection<int, \App\Domain\Vendor\Models\Vendor>|\App\Domain\Vendor\Models\Vendor[]                 $vendors
 * @property Collection<int, \App\Domain\Country\Models\Country>|\App\Domain\Country\Models\Country[]             $countries
 * @property Collection<int, CompanyCategory>                                                                     $categories
 * @property Collection<int, \App\Domain\Industry\Models\Industry>                                                $industries
 * @property Collection<int, CompanyAlias>                                                                        $aliases
 * @property \App\Domain\User\Models\User|null                                                                    $user
 * @property \App\Domain\SalesUnit\Models\SalesUnit|null                                                          $salesUnit
 * @property array                                                                                                $logo
 */
class Company extends Model implements HasImagesDirectory, WithLogo, ActivatableInterface, HasOrderedScope, SearchableEntity, HasOwner, LinkedToAppointments, HasOwnAppointments, LinkedToTasks, HasOwnNotes, HasSalesUnit, ProvidesIdForHumans, HasSharingUserRelations
{
    use Uuid;
    use Multitenantable;
    use BelongsToUser;
    use BelongsToVendors;
    use BelongsToAddresses;
    use BelongsToContacts;
    use Activatable;
    use Searchable;
    use HasQuotes;
    use SoftDeletes;
    use HasRelationships;
    use HasFactory;
    use HasTimestamps;

    const SYSTEM = 1 << 0;
    const FROZEN_SOURCE = 1 << 1;
    const SYNC_PROTECTED = 1 << 2;

    protected $fillable = [
        'name', 'short_code', 'type', 'source', 'vat', 'email', 'website', 'phone', 'default_vendor_id',
        'default_country_id', 'default_template_id',
    ];

    protected $casts = [
        'status' => CompanyStatusEnum::class,
        'customer_type' => CustomerTypeEnum::class,
        'creation_date' => 'datetime',
    ];

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    public function getFlag(int $flag): bool
    {
        return ($this->flags & $flag) === $flag;
    }

    public static function query(): CompanyBuilder
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::query();
    }

    public function newQuery(): CompanyBuilder
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return parent::newQuery();
    }

    public function newEloquentBuilder($query): CompanyBuilder
    {
        return new CompanyBuilder($query);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(CompanyAlias::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            related: CompanyCategory::class,
            table: 'category_company',
            foreignPivotKey: 'company_id',
            relatedPivotKey: 'category_id'
        );
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(\App\Domain\Industry\Models\Industry::class);
    }

    public function vendors(): BelongsToMany
    {
        return tap($this->belongsToMany(\App\Domain\Vendor\Models\Vendor::class), function (BelongsToMany $relation) {
            $relation->leftJoin($this->getTable(), $relation->getQualifiedForeignPivotKeyName(),
                $this->getQualifiedKeyName())
                ->select("{$relation->getRelated()->getTable()}.*")
                ->addSelect([
                    "{$this->qualifyColumn('default_vendor_id')} as company_default_vendor_id",
                    "{$this->qualifyColumn('default_country_id')} as company_default_country_id",
                ])
                ->orderByRaw("field({$relation->getQualifiedRelatedKeyName()}, `company_default_vendor_id`, null) desc");
        });
    }

    public function locations(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->addresses(), (new Address())->location());
    }

    public function countries(): HasManyDeep
    {
        return $this->hasManyDeep(
            Country::class,
            ['company_vendor', \App\Domain\Vendor\Models\Vendor::class, 'country_vendor'],
        )->groupBy('countries.id', 'company_vendor.company_id');
    }

    public function defaultVendor(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Vendor\Models\Vendor::class);
    }

    public function defaultCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function defaultTemplate(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class, 'default_template_id');
    }

    public function customerTotals(): HasMany
    {
        return $this->hasMany(CustomerTotal::class);
    }

    public function scopeWithTotalQuotedValue(Builder $query): Builder
    {
        return $query->addSelect([
            'total_quoted_value' => function (BaseBuilder $baseBuilder) {
                return $baseBuilder
                    ->selectRaw('SUM(total_price)')
                    ->from('quote_totals')
                    ->whereColumn('quote_totals.company_id', 'companies.id');
            },
        ])
            ->withCasts([
                'total_quoted_value' => 'decimal:2',
            ]);
    }

    public function prioritizeDefaultCountryOnVendors(): static
    {
        return tap($this, function () {
            $this->vendors->each(function (Vendor $vendor) {
                $vendor->setRelation(
                    relation: 'countries',
                    value: $vendor->countries
                        ->sortByDesc(function (Country $country) {
                            return $this->default_country_id === $country->getKey();
                        })
                        ->values()
                );
            });
        });
    }

    public function scopeVendor($query, string $id)
    {
        return $query->whereHas('vendors', fn ($query) => $query->where('vendors.id', $id));
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw('field(`vat`, ?, null) desc', [CP_DEF_VAT]);
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }

    public function withAppends()
    {
        return $this->append('logo');
    }

    public function toSearchArray(): array
    {
        return [
            'name' => $this->name,
            'vat' => $this->vat,
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'categories' => $this->categories->pluck('name')->all(),
            'created_at' => transform($this->{$this->getCreatedAtColumn()},
                static fn (\DateTimeInterface|string $dateTime) => Carbon::parse($dateTime)),
        ];
    }

    public function createLogo($file, $fake = false)
    {
        return ThumbHelper::createLogoThumbnails($this, $file, $fake);
    }

    public function thumbnailProperties(): array
    {
        return [
            'x1' => [
                'width' => 60,
                'height' => 30,
            ],
            'x2' => [
                'width' => 120,
                'height' => 60,
            ],
            'x3' => [
                'width' => 240,
                'height' => 120,
            ],
        ];
    }

    public function imagesDirectory(): string
    {
        return 'images/'.Str::snake(Str::plural(class_basename($this)));
    }

    public function appendLogo()
    {
        return $this->makeVisible('logo')->setAppends(['logo']);
    }

    public function getLogoAttribute()
    {
        return ThumbHelper::getImageThumbnails(
            $this->image,
            $this->thumbnailProperties()
        );
    }

    public function getLogoDimensionsAttribute()
    {
        return ThumbHelper::getLogoDimensionsFromImage(
            $this->image,
            $this->thumbnailProperties(),
            Str::snake(class_basename(static::class))
        );
    }

    public function getLogoSelectionAttribute()
    {
        return ThumbHelper::getLogoDimensionsFromImage(
            $this->image,
            $this->thumbnailProperties(),
            Str::snake(class_basename(static::class)),
            ThumbHelper::MAP | ThumbHelper::ABS_PATH
        );
    }

    public function getLogoSelectionWithKeysAttribute()
    {
        return ThumbHelper::getLogoDimensionsFromImage(
            $this->image,
            $this->thumbnailProperties(),
            Str::snake(class_basename(static::class)),
            ThumbHelper::MAP
        );
    }

    public function image()
    {
        return $this->morphOne(\App\Domain\Image\Models\Image::class, 'imageable')->cacheForever();
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(\App\Domain\Worldwide\Models\Opportunity::class, 'primary_account_id');
    }

    public function opportunitiesWhereEndUser(): HasMany
    {
        return $this->hasMany(\App\Domain\Worldwide\Models\Opportunity::class, 'end_user_id');
    }

    public function worldwideQuotes(): HasManyThrough
    {
        return $this->hasManyThrough(WorldwideQuote::class, \App\Domain\Worldwide\Models\Opportunity::class, firstKey: 'primary_account_id');
    }

    public function worldwideQuoteVersions(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->worldwideQuotes(),
            (new WorldwideQuote())->versions()
        );
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class);
    }

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Attachment::class, 'attachable', relatedPivotKey: 'attachment_id');
    }

    public function quoteTemplates(): HasMany
    {
        return $this->hasMany(QuoteTemplate::class);
    }

    public function contractTemplates(): HasMany
    {
        return $this->hasMany(ContractTemplate::class);
    }

    public function hpeContractTemplates(): HasMany
    {
        return $this->hasMany(HpeContractTemplate::class);
    }

    public function salesOrderTemplates(): HasMany
    {
        return $this->hasMany(SalesOrderTemplate::class);
    }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\User\Models\User::class, 'user_id');
    }

    public function sharingUserRelations(): HasMany
    {
        return $this->hasMany(ModelHasSharingUsers::class, 'model_id');
    }

    public function sharingUsers(): MorphToMany
    {
        $pivot = new ModelHasSharingUsers();

        return $this->morphToMany(User::class, name: $pivot->related()->getRelationName(), table: $pivot->getTable());
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SalesUnit\Models\SalesUnit::class);
    }

    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, name: 'model',
            table: (new \App\Domain\Task\Models\ModelHasTasks())->getTable());
    }

    public function ownAppointments(): MorphToMany
    {
        return $this->morphToMany(Appointment::class, name: 'model', table: (new ModelHasAppointments())->getTable());
    }

    public function notes(): MorphToMany
    {
        return $this->morphToMany(
            related: Note::class,
            name: 'model',
            table: (new ModelHasNotes())->getTable(),
            relatedPivotKey: 'note_id',
        )->using(ModelHasNotes::class);
    }

    public function syncErrors(): MorphMany
    {
        return $this->morphMany(related: PipelinerSyncError::class, name: 'entity');
    }

    public function getIdForHumans(): string
    {
        return $this->name;
    }
}

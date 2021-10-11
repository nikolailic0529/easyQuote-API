<?php

namespace App\Models;

use App\Contracts\{ActivatableInterface, HasImagesDirectory, HasOrderedScope, SearchableEntity, WithLogo};
use App\Models\{Data\Country, Quote\WorldwideQuote, Template\QuoteTemplate};
use App\Models\Customer\CustomerTotal;
use App\Services\ThumbHelper;
use App\Traits\{Activatable,
    Auth\Multitenantable,
    BelongsToAddresses,
    BelongsToContacts,
    BelongsToUser,
    BelongsToVendors,
    Quote\HasQuotes,
    QuoteTemplate\HasQuoteTemplates,
    Search\Searchable,
    Systemable,
    Uuid};
use Illuminate\Database\Eloquent\{Builder, Model, SoftDeletes,};
use Illuminate\Database\Eloquent\{Collection,
    Relations\BelongsTo,
    Relations\BelongsToMany,
    Relations\HasManyThrough,
    Relations\MorphToMany};
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Str;
use Staudenmeir\EloquentHasManyDeep\{HasManyDeep, HasRelationships,};

/**
 * Class Company
 *
 * @property string|null $user_id
 * @property string|null $default_country_id
 * @property string|null $default_vendor_id
 * @property string|null $name
 * @property string|null $short_code
 * @property string|null $vs_company_code
 * @property string|null $type
 * @property string|null $source
 * @property string|null $category
 * @property string|null $email
 * @property string|null $vat
 * @property string|null $vat_type
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $activated_at
 *
 * @property Image|null $image
 * @property Collection<Address>|Address[] $addresses
 * @property Collection<Contact>|Contact[] $contacts
 * @property-read Collection<Opportunity>|Opportunity[] $opportunities
 * @property-read Collection<WorldwideQuote>|WorldwideQuote[] $worldwideQuotes
 * @property-read Collection<CompanyNote>|CompanyNote[] $companyNotes
 * @property-read Collection<Vendor>|Vendor[] $vendors
 * @property-read Collection<Country>|Country[] $countries
 */
class Company extends Model implements HasImagesDirectory, WithLogo, ActivatableInterface, HasOrderedScope, SearchableEntity
{
    use Uuid,
        Multitenantable,
        BelongsToUser,
        BelongsToVendors,
        BelongsToAddresses,
        BelongsToContacts,
        Activatable,
        Searchable,
        Systemable,
        HasQuoteTemplates,
        HasQuotes,
        SoftDeletes,
        HasRelationships;

    protected $fillable = [
        'name', 'short_code', 'type', 'category', 'source', 'vat', 'email', 'website', 'phone', 'default_vendor_id', 'default_country_id', 'default_template_id',
    ];

    public function vendors(): BelongsToMany
    {
        return tap($this->belongsToMany(Vendor::class), function (BelongsToMany $relation) {

            $relation->leftJoin($this->getTable(), $relation->getQualifiedForeignPivotKeyName(), $this->getQualifiedKeyName())
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
        return $this->hasManyDeepFromRelations($this->addresses(), (new Address)->location());
    }

    public function countries(): HasManyDeep
    {
        return $this->hasManyDeep(
            Country::class,
            ['company_vendor', Vendor::class, 'country_vendor'],
        )->groupBy('countries.id', 'company_vendor.company_id');
    }

    public function defaultVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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
        return $query->whereHas('vendors', fn($query) => $query->where('vendors.id', $id));
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("field(`vat`, ?, null) desc", [CP_DEF_VAT]);
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
            'created_at' => $this->created_at,
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
            ThumbHelper::WITH_KEYS | ThumbHelper::ABS_PATH
        );
    }

    public function getLogoSelectionWithKeysAttribute()
    {
        return ThumbHelper::getLogoDimensionsFromImage(
            $this->image,
            $this->thumbnailProperties(),
            Str::snake(class_basename(static::class)),
            ThumbHelper::WITH_KEYS
        );
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable')->cacheForever();
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'primary_account_id');
    }

    public function worldwideQuotes(): HasManyThrough
    {
        return $this->hasManyThrough(WorldwideQuote::class, Opportunity::class, firstKey: 'primary_account_id');
    }

    public function worldwideQuoteVersions(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations(
            $this->worldwideQuotes(),
            (new WorldwideQuote())->versions()
        );
    }

    public function companyNotes(): HasMany
    {
        return $this->hasMany(CompanyNote::class);
    }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class);
    }

    public function attachments(): MorphToMany
    {
        return $this->morphToMany(Attachment::class, 'attachable', relatedPivotKey: 'attachment_id');
    }
}

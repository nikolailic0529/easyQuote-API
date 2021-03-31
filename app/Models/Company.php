<?php

namespace App\Models;

use App\Contracts\{ActivatableInterface, HasImagesDirectory, HasOrderedScope, SearchableEntity, WithLogo};
use App\Models\{Data\Country, Template\QuoteTemplate,};
use App\Models\Customer\CustomerTotal;
use App\Services\ThumbHelper;
use App\Traits\{Activatable,
    Activity\LogsActivity,
    Auth\Multitenantable,
    BelongsToAddresses,
    BelongsToContacts,
    BelongsToUser,
    BelongsToVendors,
    Quote\HasQuotes,
    QuoteTemplate\HasQuoteTemplates,
    Search\Searchable,
    Systemable,
    Uuid
};
use Illuminate\Database\Eloquent\{Builder, Model, SoftDeletes,};
use Illuminate\Database\Eloquent\{Collection, Relations\BelongsTo, Relations\BelongsToMany};
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Staudenmeir\EloquentHasManyDeep\{HasManyDeep, HasRelationships,};

/**
 * Class Company
 *
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
 *
 * @property Image|null $image
 * @property Collection<Address>|Address[] $addresses
 * @property Collection<Contact>|Contact[] $contacts
 */
class Company extends Model implements HasImagesDirectory, WithLogo, ActivatableInterface, HasOrderedScope, SearchableEntity
{
    public const TYPES = ['Internal', 'External'];

    public const CATEGORIES = ['End User', 'Reseller', 'Business Partner'];

    public const SOURCES = ['EQ', 'S4'];

    public const INT_TYPE = 'Internal';

    public const EXT_TYPE = 'External';

    public const REGULAR_RELATIONSHIPS = [
        'defaultCountry', 'defaultVendor', 'defaultTemplate', 'vendors', 'addresses.country', 'contacts', 'vendors.countries', 'addresses.country', 'contacts',
    ];

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
        LogsActivity,
        SoftDeletes,
        HasRelationships;

    protected $fillable = [
        'name', 'short_code', 'type', 'category', 'source', 'vat', 'email', 'website', 'phone', 'default_vendor_id', 'default_country_id', 'default_template_id',
    ];

    protected static array $logAttributes = [
        'name', 'category', 'vat', 'type', 'email', 'category', 'website', 'phone', 'defaultVendor.name', 'defaultCountry.name', 'defaultTemplate.name',
    ];

    protected static bool $logOnlyDirty = true;

    protected static bool $submitEmptyLogs = false;

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class)
            ->addSelect([
                'company_default_vendor' => Company::query()->selectRaw('default_vendor_id')
                    ->whereColumn('company_vendor.company_id', 'companies.id')
                    ->limit(1)
                    ->toBase()
            ])
            ->orderByRaw("field(`vendors`.`id`, `company_default_vendor`, null) desc");
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
            'total_quoted_value' => fn($q) => $q
                ->select('total_value')
                ->from('customer_totals')
                ->whereColumn('customer_totals.company_id', 'companies.id')
                ->limit(1),
        ])
            ->withCasts([
                'total_quoted_value' => 'decimal:2',
            ]);
    }

    public function sortVendorsCountries(): self
    {
        $vendors = $this->vendors->map(
            fn($vendor) => $vendor->setRelation(
                'countries',
                $vendor->countries->sortByDesc(fn($country) => ($this->default_country_id === $country->id))->values()
            )
        );

        return $this->setRelation('vendors', $vendors);
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
}

<?php

namespace App\Models;

use App\Contracts\{ActivatableInterface, HasImagesDirectory, SearchableEntity, WithLogo};
use App\Models\Data\Country;
use App\Services\ThumbHelper;
use App\Traits\{Activatable,
    Activity\LogsActivity,
    Auth\Multitenantable,
    BelongsToCountries,
    BelongsToUser,
    Quote\HasQuotes,
    QuoteTemplate\HasQuoteTemplates,
    Search\Searchable,
    Systemable,
    Uuid
};
use Illuminate\Database\Eloquent\{Collection, Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property string|null $name
 * @property string|null $short_code
 *
 * @property-read array|null $logo
 * @property-read Collection<Country>|Country[] $countries
 */
class Vendor extends Model implements HasImagesDirectory, WithLogo, ActivatableInterface, SearchableEntity
{
    use Uuid,
        Multitenantable,
        BelongsToCountries,
        BelongsToUser,
        HasQuotes,
        HasQuoteTemplates,
        Activatable,
        SoftDeletes,
        Searchable,
        Systemable,
        LogsActivity;

    protected $fillable = [
        'name', 'short_code',
    ];

//    protected $hidden = [
//        'pivot', 'deleted_at', 'image', 'image_id', 'is_system',
//    ];

    protected $appends = [
        'logo', // TODO: remove appends
    ];

    protected static $logAttributes = [
        'name', 'short_code',
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function toSearchArray(): array
    {
        return [
            'name' => $this->name,
            'short_code' => $this->short_code,
            'created_at' => $this->created_at,
        ];
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
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
        return $this->morphOne(Image::class, 'imageable')->cacheForever();
    }

    public function countries(): BelongsToMany
    {
        return tap($this->belongsToMany(Country::class), function (BelongsToMany $relation) {
          $relation->orderBy('name');
        });
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        if (isset($array['logo']) && $array['logo'] === []) {
            $array['logo'] = null;
        }

        return $array;
    }
}

<?php

namespace App\Domain\Contact\Models;

use App\Domain\Address\Models\Address;
use App\Domain\Appointment\Contracts\HasOwnAppointments;
use App\Domain\Appointment\Contracts\LinkedToAppointments;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Appointment\Models\ModelHasAppointments;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Enum\GenderEnum;
use App\Domain\Image\Contracts\HasImagesDirectory;
use App\Domain\Image\Models\Image;
use App\Domain\Language\Models\Language;
use App\Domain\SalesUnit\Contracts\HasSalesUnit;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Shared\Eloquent\Concerns\{Activatable};
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\User\Models\User;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

/**
 * @property string|null     $pl_reference
 * @property string|null     $contact_type
 * @property GenderEnum|null $gender
 * @property string|null     $contact_name
 * @property string|null     $first_name
 * @property string|null     $last_name
 * @property string|null     $email
 * @property string|null     $mobile
 * @property string|null     $phone
 * @property string|null     $job_title
 * @property bool|null       $is_verified
 * @property bool|null       $is_default
 * @property Address|null    $address
 * @property string          $contact_representation
 * @property User|null       $user
 * @property SalesUnit|null  $salesUnit
 * @property Language|null   $language
 */
class Contact extends Model implements HasImagesDirectory, SearchableEntity, LinkedToAppointments, HasOwnAppointments, ProvidesIdForHumans, HasSalesUnit
{
    use Uuid;
    use SoftDeletes;
    use Searchable;
    use Activatable;
    use HasFactory;

    protected $fillable = [
        'contact_type', 'contact_name', 'job_title', 'first_name', 'last_name', 'mobile', 'phone', 'email', 'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'gender' => GenderEnum::class,
    ];

    protected $hidden = [
        'deleted_at', 'contact_name', 'pivot',
    ];

    protected static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(SalesUnit::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable')->cacheForever();
    }

    public function contactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class);
    }

    public function ownAppointments(): MorphToMany
    {
        return $this->morphToMany(Appointment::class, name: 'model', table: (new ModelHasAppointments())->getTable());
    }

    public function companies(): BelongsToMany
    {
        return $this->morphedByMany(Company::class, 'contactable')->withPivot('is_default');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->whereContactType($type);
    }

    public function scopeWithoutType(Builder $query): Builder
    {
        return $query->whereNull('contact_type');
    }

    public function imagesDirectory(): string
    {
        return 'images/contacts';
    }

    public function toSearchArray(): array
    {
        return Arr::except($this->toArray(), ['picture', 'image']);
    }

    public function getItemNameAttribute()
    {
        return isset($this->contact_type)
            ? "{$this->contact_type} Contact ({$this->contact_name})"
            : "Contact ({$this->email})";
    }

    public function withAppends()
    {
        return $this->append('picture');
    }

    public function createImage($file, array $properties = [])
    {
        if (!$file instanceof UploadedFile || !$this instanceof HasImagesDirectory) {
            return $this;
        }

        $modelImagesDir = $this->imagesDirectory();

        $image = ImageManagerStatic::make($file->get());

        if (filled($properties) && isset($properties['width']) && isset($properties['height'])) {
            $image->resize($properties['width'], $properties['height'], static function ($constraint): void {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $storageDir = "public/{$modelImagesDir}";
        !Storage::exists($storageDir) && Storage::makeDirectory($storageDir);

        $original = "{$modelImagesDir}/{$file->hashName()}";
        $image->save(Storage::path("public/{$original}"));

        $this->image()->delete();

        $this->image()->create(compact('original'));

        $this->image()->flushQueryCache();

        return $this->load('image');
    }

    public function getPictureAttribute()
    {
        if (!isset($this->image->original_image)) {
            return null;
        }

        return asset('storage/'.$this->image->original_image);
    }

    public function getContactRepresentationAttribute(): string
    {
        return sprintf('ContactType=`%s` JobTitle=`%s` FirstName=`%s` LastName=`%s` Mobile=`%s` Phone=`%s` Email=`%s` IsVerified=%s',
            $this->contact_type,
            $this->job_title ?? '',
            $this->first_name,
            $this->last_name,
            $this->mobile,
            $this->phone,
            $this->email,
            $this->is_verified ? 'true' : 'false',
        );
    }

    public function isEmpty(): bool
    {
        $attributes = ['job_title', 'first_name', 'last_name', 'mobile', 'phone', 'email'];

        foreach ($attributes as $attribute) {
            if (filled(data_get($this, $attribute))) {
                return false;
            }
        }

        return true;
    }

    public function getIdForHumans(): string
    {
        return trim("$this->first_name $this->last_name");
    }
}

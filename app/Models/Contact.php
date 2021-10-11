<?php

namespace App\Models;

use App\Contracts\HasImagesDirectory;
use App\Traits\{Activatable, Search\Searchable, Uuid};
use App\Contracts\SearchableEntity;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\{Builder, Model, SoftDeletes};
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic;

/**
 * @property string|null $contact_type
 * @property string|null $contact_name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $email
 * @property string|null $mobile
 * @property string|null $phone
 * @property string|null $job_title
 * @property bool|null $is_verified
 * @property bool|null $is_default
 *
 * @property-read string $contact_representation
 */
class Contact extends Model implements HasImagesDirectory, SearchableEntity
{
    use Uuid, SoftDeletes, Searchable, Activatable;

    protected $fillable = [
        'contact_type', 'contact_name', 'job_title', 'first_name', 'last_name', 'mobile', 'phone', 'email', 'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    protected $hidden = [
        'deleted_at', 'contact_name', 'pivot',
    ];

    public function contactable(): MorphTo
    {
        return $this->morphTo();
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

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable')->cacheForever();
    }

    public function createImage($file, array $properties = [])
    {
        if (!$file instanceof UploadedFile || !$this instanceof HasImagesDirectory) {
            return $this;
        }

        $modelImagesDir = $this->imagesDirectory();

        $image = ImageManagerStatic::make($file->get());

        if (filled($properties) && isset($properties['width']) && isset($properties['height'])) {
            $image->resize($properties['width'], $properties['height'], function ($constraint) {
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
        return sprintf("ContactType=`%s` JobTitle=`%s` FirstName=`%s` LastName=`%s` Mobile=`%s` Phone=`%s` Email=`%s` IsVerified=%s",
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
}

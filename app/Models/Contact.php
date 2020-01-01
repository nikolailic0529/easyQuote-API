<?php

namespace App\Models;

use App\Contracts\WithImage;
use App\Traits\{
    Activatable,
    Image\HasImage,
    Image\HasPictureAttribute,
    Search\Searchable
};
use Illuminate\Database\Eloquent\{
    Builder,
    SoftDeletes
};
use Arr;

class Contact extends BaseModel implements WithImage
{
    use SoftDeletes, HasImage, HasPictureAttribute, Searchable, Activatable;

    protected $fillable = [
        'contact_type', 'contact_name', 'job_title', 'first_name', 'last_name', 'mobile', 'phone', 'email', 'is_verified'
    ];

    protected $casts = [
        'is_verified' => 'boolean'
    ];

    protected $hidden = [
        'deleted_at', 'contact_type', 'contact_name', 'pivot'
    ];

    public function contactable()
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

    public function toSearchArray()
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
}

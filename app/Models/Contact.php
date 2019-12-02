<?php

namespace App\Models;

use App\Traits\{
    Image\HasImage,
    Image\HasPictureAttribute
};
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends UuidModel
{
    use SoftDeletes, HasImage, HasPictureAttribute;

    protected $fillable = [
        'contact_type', 'job_title', 'first_name', 'last_name', 'mobile', 'phone', 'email'
    ];

    public function contactable()
    {
        return $this->morphTo();
    }

    public function scopeType($query, string $type)
    {
        return $query->whereContactType($type);
    }
}

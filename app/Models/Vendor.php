<?php namespace App\Models;

use App\Contracts\WithImage;
use App\Traits \ {
    Activatable,
    BelongsToCountries,
    BelongsToUser,
    Image\HasImage,
    Search\Searchable
};
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Vendor extends UuidModel implements WithImage
{
    use BelongsToCountries,
        BelongsToUser,
        HasImage,
        Activatable,
        SoftDeletes,
        Searchable;

    protected $fillable = [
        'name', 'short_code'
    ];

    protected $hidden = [
        'pivot', 'deleted_at', 'image', 'image_id'
    ];

    protected $casts = [
        'is_system' => 'boolean'
    ];

    protected $appends = [
        'logo'
    ];

    public function getLogoAttribute()
    {
        if(!isset($this->image)) {
            return null;
        }

        return asset("storage/{$this->image->thumbnail}");
    }

    public function thumbnailProperties(): array
    {
        return [
            'width' => 60,
            'height' => 30
        ];
    }

    public function imagesDirectory(): string
    {
        return 'images/vendors';
    }

    public function scopeCurrentUser(Builder $query)
    {
        return $query->where(function ($query) {
            $query->where('is_system', true)
                ->orWhere('user_id', request()->user()->id);
        });
    }

    public function isSystem()
    {
        return $this->getAttribute('is_system');
    }
}

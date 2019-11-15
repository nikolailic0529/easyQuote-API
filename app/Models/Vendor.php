<?php

namespace App\Models;

use App\Contracts\{
    ActivatableInterface,
    WithImage,
    WithLogo
};
use App\Traits\{
    Activatable,
    BelongsToCountries,
    BelongsToUser,
    Image\HasImage,
    Image\HasLogo,
    Search\Searchable,
    Systemable
};
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends UuidModel implements WithImage, WithLogo, ActivatableInterface
{
    use HasLogo,
        HasImage,
        BelongsToCountries,
        BelongsToUser,
        Activatable,
        SoftDeletes,
        Searchable,
        Systemable;

    protected $fillable = [
        'name', 'short_code'
    ];

    protected $hidden = [
        'pivot', 'deleted_at', 'image', 'image_id', 'is_system'
    ];

    protected $appends = [
        'logo'
    ];

    public function toSearchArray()
    {
        $this->makeHidden('logo');
        return $this->toArray();
    }
}

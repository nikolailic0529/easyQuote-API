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
    Systemable,
    Quote\HasQuotes,
    QuoteTemplate\HasQuoteTemplates,
    Activity\LogsActivity
};
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends UuidModel implements WithImage, WithLogo, ActivatableInterface
{
    use HasLogo,
        HasImage,
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
        'name', 'short_code'
    ];

    protected $hidden = [
        'pivot', 'deleted_at', 'image', 'image_id', 'is_system'
    ];

    protected $appends = [
        'logo'
    ];

    protected static $logAttributes = [
        'name', 'short_code'
    ];

    protected static $logOnlyDirty = true;

    protected static $submitEmptyLogs = false;

    public function toSearchArray()
    {
        $this->makeHidden('logo');
        return $this->toArray();
    }

    public function inUse()
    {
        return $this->quotes()->exists() || $this->quoteTemplates()->exists();
    }

    public function getItemNameAttribute()
    {
        return $this->name;
    }
}

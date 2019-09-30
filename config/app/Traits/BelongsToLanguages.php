<?php namespace App\Traits;

use App\Models\Data\Language;

trait BelongsToLanguages
{
    public function languages()
    {
        return $this->belongsToMany(Language::class);
    }
}

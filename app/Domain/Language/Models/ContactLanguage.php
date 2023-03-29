<?php

namespace App\Domain\Language\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ContactLanguage extends Pivot
{
    protected $primaryKey = 'language_id';
    protected $table = 'contact_languages';

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}

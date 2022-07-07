<?php

namespace App\Models\Note;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelHasNotes extends MorphPivot
{
    protected $table = 'model_has_notes';

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('model');
    }
}

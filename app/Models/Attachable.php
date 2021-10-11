<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property Model|null $related
 */
class Attachable extends MorphPivot
{
    protected $table = 'attachables';
    protected $primaryKey = 'attachment_id';

    public function related(): MorphTo
    {
        return $this->morphTo('attachable');
    }
}

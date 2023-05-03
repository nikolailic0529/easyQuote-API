<?php

namespace App\Domain\Attachment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string|null $attachment_id
 * @property Model|null  $related
 */
class Attachable extends MorphPivot
{
    public $timestamps = false;

    protected $table = 'attachables';
    protected $primaryKey = 'attachment_id';

    public function related(): MorphTo
    {
        return $this->morphTo('attachable');
    }
}

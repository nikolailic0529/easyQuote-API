<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $filepath
 * @property string|null $filename
 * @property string|null $extension
 * @property int|null $size
 * @property string|null $type
 */
class Attachment extends Model
{
    use Uuid;

    public const TYPES = [
        'Maintenance Contract', 'Distribution Quotation', 'Email', 'Proof of delivery', 'Customer Purchase Order', 'Image'
    ];

    protected $fillable = [
        'type', 'filepath', 'filename', 'extension', 'size'
    ];
}

<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use Uuid;

    public const TYPES = ['Maintenance Contract', 'Distribution Quotation', 'Email', 'Proof of delivery', 'Customer Purchase Order'];

    protected $fillable = [
        'type', 'filepath', 'filename', 'extension', 'size'
    ];
}

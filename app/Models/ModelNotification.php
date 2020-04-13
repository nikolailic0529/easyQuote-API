<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class ModelNotification extends Model
{
    use Uuid;

    protected $fillable = [
        'notification_key'
    ];
}

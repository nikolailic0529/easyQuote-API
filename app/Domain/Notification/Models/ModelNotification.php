<?php

namespace App\Domain\Notification\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

class ModelNotification extends Model
{
    use Uuid;

    protected $fillable = [
        'notification_key',
    ];
}

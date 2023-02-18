<?php

namespace App\Domain\Pipeliner\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $pl_reference
 * @property string|null $signature
 * @property string|null $url
 * @property array|null  $events
 * @property bool|null   $insecure_ssl
 * @property array|null  $options
 */
class PipelinerWebhook extends Model
{
    use Uuid;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'events' => 'array',
        'options' => 'array',
    ];
}

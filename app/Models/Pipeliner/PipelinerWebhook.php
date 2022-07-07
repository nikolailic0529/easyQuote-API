<?php

namespace App\Models\Pipeliner;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $pl_reference
 * @property string|null $signature
 * @property string|null $url
 * @property array|null $events
 * @property bool|null $insecure_ssl
 * @property array|null $options
 *
 */
class PipelinerWebhook extends Model
{
    use Uuid, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'events' => 'array',
        'options' => 'array'
    ];
}

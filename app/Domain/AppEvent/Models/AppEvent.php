<?php

namespace App\Domain\AppEvent\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Domain\AppEvent\Models\AppEvent.
 *
 * @property string                     $id
 * @property string                     $name        Event name
 * @property \Illuminate\Support\Carbon $occurred_at Event occurrence timestamp
 * @property ArrayObject                $payload     Event payload
 */
class AppEvent extends Model
{
    use Uuid;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload' => AsArrayObject::class,
    ];
}

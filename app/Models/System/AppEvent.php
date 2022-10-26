<?php

namespace App\Models\System;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\System\AppEvent
 *
 * @property string $id
 * @property string $name Event name
 * @property \Illuminate\Support\Carbon $occurred_at Event occurrence timestamp
 * @property ArrayObject $payload Event payload
 */
class AppEvent extends Model
{
    use Uuid, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
        'payload' => AsArrayObject::class,
    ];
}

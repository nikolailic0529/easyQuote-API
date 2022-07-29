<?php

namespace App\Models;

use App\Traits\Uuid;
use Database\Factories\SalesUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string|null $pl_reference
 * @property string|null $unit_name
 * @property int|null $entity_order
 * @property bool|null $is_default
 * @property bool|null $is_enabled
 */
class SalesUnit extends Model
{
    use Uuid, HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    protected $hidden = [
        'pivot', 'deleted_at',
    ];

    protected static function newFactory(): SalesUnitFactory
    {
        return SalesUnitFactory::new();
    }
}

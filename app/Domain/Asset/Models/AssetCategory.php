<?php

namespace App\Domain\Asset\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

/**
 * @property string|null $name
 */
class AssetCategory extends Model
{
    use Uuid;
    use QueryCacheable;

    protected $fillable = ['name'];
}

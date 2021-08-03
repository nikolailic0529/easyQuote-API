<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

/**
 * @property string|null $name
 */
class AssetCategory extends Model
{
    use Uuid, QueryCacheable;

    protected $fillable = ['name'];
}

<?php

namespace App\Models;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    use Uuid;

    protected $fillable = ['name'];
}

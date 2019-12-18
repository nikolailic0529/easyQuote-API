<?php

namespace App\Models\CachedRelation;

use Illuminate\Database\Eloquent\Model;

class CachedRelations extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function __get($key)
    {
        if ($this->getAttribute($key) === null) {
            return new CachedRelation([]);
        }

        return parent::__get($key);
    }
}

<?php

namespace App\Models\CachedRelation;

use Illuminate\Database\Eloquent\Model;

class CachedRelationWrapper extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $keyType = 'string';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $relations = [])
    {
        $relations = array_map(function ($relation) {
            return CachedRelation::make($relation);
        }, $relations);

        $this->syncOriginal();

        $this->fill($relations);
    }

    public function __get($key)
    {
        if ($this->getAttribute($key) === null) {
            return new CachedRelation;
        }

        return parent::__get($key);
    }
}

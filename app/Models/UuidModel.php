<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;
use Fico7489\Laravel\EloquentJoin\Traits\EloquentJoin;
use Fico7489\Laravel\EloquentJoin\Traits\ExtendRelationsTrait;

class UuidModel extends Model
{
    use EloquentJoin, ExtendRelationsTrait;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $formatDates = true;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            static::generateId($model);
        });

        static::replicating(function ($model) {
            static::generateId($model);
        });
    }

    protected function propertyExists(string $property)
    {
        return isset($this->{$property});
    }

    public function makeHiddenExcept($attributes)
    {
        $attributes = (array) $attributes;

        $this->hidden = array_diff($this->visible, $attributes);

        $this->visible = array_unique(array_merge($this->hidden, $attributes));

        return $this;
    }

    public function getCreatedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }

    public function getUpdatedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }

    public function getDeletedAtAttribute($value)
    {
        return carbon_format($value, config('date.format_with_time'));
    }

    /**
     * Register a generated model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function generated($callback)
    {
        static::registerModelEvent('generated', $callback);
    }

    public static function generateId(Model $model)
    {
        $model->{$model->getKeyName()} = Uuid::generate(4)->string;
        $model->fireModelEvent('generated', false);
    }

    public function saveWithoutEvents(array $options = [])
    {
        return static::withoutEvents(function() use ($options) {
            return $this->save($options);
        });
    }
}

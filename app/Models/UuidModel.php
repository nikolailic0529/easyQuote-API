<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;
use App\Builder\ApiBuilder;
use Fico7489\Laravel\EloquentJoin\Traits\ExtendRelationsTrait;

class UuidModel extends Model
{
    use ExtendRelationsTrait;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $formatDates = true;

    protected $dateTimeFormat = 'd/m/Y';

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

    public function newEloquentBuilder($query)
    {
        $newEloquentBuilder = new ApiBuilder($query);

        if (isset($this->useTableAlias)) {
            $newEloquentBuilder->setUseTableAlias($this->useTableAlias);
        }

        if (isset($this->appendRelationsCount)) {
            $newEloquentBuilder->setAppendRelationsCount($this->appendRelationsCount);
        }

        if (isset($this->leftJoin)) {
            $newEloquentBuilder->setLeftJoin($this->leftJoin);
        }

        if (isset($this->aggregateMethod)) {
            $newEloquentBuilder->setAggregateMethod($this->aggregateMethod);
        }

        return $newEloquentBuilder;
    }

    public function getCreatedAtAttribute($date)
    {
        return $this->formatDate($date);
    }

    public function getUpdatedAtAttribute($date)
    {
        return $this->formatDate($date);
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

    protected function formatDate($date)
    {
        if (!isset($date) || !$this->formatDates) {
            return $date;
        }

        return now()->createFromFormat('Y-m-d H:i:s', $date)->format($this->dateTimeFormat);
    }
}

<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;
use App\Builder\ApiBuilder;
use Carbon\Carbon;

class UuidModel extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->{$model->getKeyName()} = Uuid::generate()->string;
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
        return new ApiBuilder($query);
    }

    public function getCreatedAtAttribute($date)
    {
        return $this->formatDate($date);
    }

    public function getUpdatedAtAttribute($date)
    {
        return $this->formatDate($date);
    }

    private function formatDate($date)
    {
        if(!isset($date)) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('d/m/Y');
    }
}

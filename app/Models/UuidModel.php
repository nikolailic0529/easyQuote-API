<?php namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

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
}

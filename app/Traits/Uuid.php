<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid as UuidGenerator;

trait Uuid
{
    public function getIncrementing()
    {
        return false;
    }

    public function getkeyType()
    {
        return 'string';
    }

    protected static function bootUuid()
    {
        static::creating(function (Model $model) {
            // Only generate UUID if it wasn't set by already.
            if (!isset($model->attributes[$model->getKeyName()])) {
                $model->incrementing = false;
                $uuidVersion = (!empty($model->uuidVersion) ? $model->uuidVersion : 4);
                $uuid = UuidGenerator::generate($uuidVersion);
                $model->attributes[$model->getKeyName()] = $uuid->string;
            }
        });
    }
}

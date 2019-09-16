<?php namespace App\Models\System;

use App\Models\UuidModel;

class SystemSetting extends UuidModel
{
    protected $fillable = [
        'key', 'value', 'type'
    ];

    protected $casts = [
        'value' => ''
    ];

    protected $types = [
        'string', 'integer', 'array'
    ];

    public $timestamps = false;

    protected function getCastType($key)
    {
        if($key === 'value') {
            return $this->type;
        }

        return parent::getCastType($key);
    }

    public function setTypeAttribute($value)
    {
        if(in_array($value, $this->types)) {
            return $this->type = $value;
        }

        return $this->type = $this->types[0];
    }
}

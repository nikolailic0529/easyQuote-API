<?php namespace App\Models;

class Image extends UuidModel
{
    protected $fillable = [
        'original', 'thumbnail'
    ];

    public function imageable()
    {
        return $this->morphTo();
    }
}

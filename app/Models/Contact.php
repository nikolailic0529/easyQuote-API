<?php namespace App\Models;

class Contact extends UuidModel
{
    public function contactable()
    {
        return $this->morphTo();
    }
}

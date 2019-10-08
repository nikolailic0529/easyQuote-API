<?php namespace App\Traits;

trait Systemable
{
    public function isSystem()
    {
        return (bool) $this->getAttribute('is_system');
    }
}

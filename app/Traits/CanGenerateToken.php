<?php namespace App\Traits;

use Arr;

trait CanGenerateToken
{
    public function generateToken(array $attributes = ['id', 'email']): string
    {
        return substr(md5(implode('', Arr::only($this->getAttributes(), $attributes)). time()), 0, 32);
    }
}

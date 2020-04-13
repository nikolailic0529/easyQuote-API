<?php namespace App\Traits;

use Arr;

trait CanGenerateToken
{
    public function generateToken(): string
    {
        return substr(md5(implode('|', $this->getAttributes()). time()), 0, 32);
    }
}

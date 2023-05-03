<?php

namespace App\Foundation\Support\Mixins;

class FileMixin
{
    public function abspath()
    {
        return function (string $value) {
            return storage_path('app\public'.str_replace(asset('storage'), '', $value));
        };
    }
}

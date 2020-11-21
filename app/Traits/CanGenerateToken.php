<?php namespace App\Traits;

use Illuminate\Support\Str;

trait CanGenerateToken
{
    public function generateToken(): string
    {
        $key = app()['config']['app.key'];

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', Str::random(40), $key);
    }
}

<?php

namespace App\Domain\Shared\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\MessageBag;

class AsMessageBag implements Castable
{
    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class() implements CastsAttributes {
            public function get($model, $key, $value, $attributes): ?MessageBag
            {
                return isset($attributes[$key]) ? new MessageBag(json_decode($attributes[$key], true)) : null;
            }

            public function set($model, $key, $value, $attributes): array
            {
                return [$key => json_encode($value->toArray())];
            }

            public function serialize($model, string $key, $value, array $attributes): mixed
            {
                return $value->toArray();
            }
        };
    }
}

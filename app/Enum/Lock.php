<?php

namespace App\Enum;

use App\Enum\Exceptions\InvalidEnumKeyException;

/**
 * @method static string CREATE_QUOTE(string $id)
 * @method static string UPDATE_QUOTE(string $id)
 * @method static string DELETE_QUOTE(string $id)
 * 
 * @method static string UPDATE_QUOTE_FILE(string $id)
 * 
 * @method static string CREATE_CONTRACT(string $id)
 * @method static string UPDATE_CONTRACT(string $id)
 * @method static string DELETE_CONTRACT(string $id)
 * 
 * @method static string UPDATE_USER(string $id)
 * @method static string DELETE_USER(string $id)
 * 
 * @method static string UPDATE_WWQUOTE(string $id)
 */
final class Lock extends Enum
{
    const
        CREATE_QUOTE = 'create-quote',
        UPDATE_QUOTE = 'update-quote',
        DELETE_QUOTE = 'delete-quote',

        UPDATE_QUOTE_FILE = 'update-quote-file',

        CREATE_CONTRACT = 'create-quote-contract',
        UPDATE_CONTRACT = 'update-quote-contract',
        DELETE_CONTRACT = 'delete-quote-contract',

        UPDATE_USER = 'update-user',
        DELETE_USER = 'delete-user'
    ;

    public static function __callStatic($name, $arguments)
    {
        return static::fromKey($name, head($arguments));
    }

    public static function fromKey(string $key, string $id)
    {
        if (!static::hasKey($key)) {
            throw new InvalidEnumKeyException($key, static::class);
        }

        return static::getValue($key).":$id";
    }
}
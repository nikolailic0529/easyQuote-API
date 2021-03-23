<?php

namespace App\Enum;

use App\Enum\Exceptions\InvalidEnumKeyException;

/**
 * \App\Enum\Lock
 *
 * @method static CREATE_QUOTE(string $id)
 * @method static UPDATE_QUOTE(string $id)
 * @method static DELETE_QUOTE(string $id)
 *
 * @method static UPDATE_QUOTE_FILE(string $id)
 *
 * @method static CREATE_CONTRACT(string $id)
 * @method static UPDATE_CONTRACT(string $id)
 * @method static DELETE_CONTRACT(string $id)
 *
 * @method static UPDATE_USER(string $id)
 * @method static DELETE_USER(string $id)
 *
 * @method static UPDATE_WWQUOTE(string $id)
 * @method static DELETE_WWQUOTE(string $id)
 *
 * @method static UPDATE_WWDISTRIBUTION(string $id)
 *
 * @method static UPDATE_WWQUOTE_NOTE(string $id)
 * @method static DELETE_WWQUOTE_NOTE(string $id)
 *
 * @method static UPDATE_OPPORTUNITY(string $id)
 * @method static DELETE_OPPORTUNITY(string $id)
 *
 * @method static UPDATE_WWASSET(string $id)
 * @method static DELETE_WWASSET(string $id)
 *
 * @method static UPDATE_SORDER(string $id)
 * @method static DELETE_SORDER(string $id)
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
        DELETE_USER = 'delete-user',

        CREATE_WWQUOTE = 'create-ww-quote',
        UPDATE_WWQUOTE = 'update-ww-quote',
        UPDATE_WWDISTRIBUTION = 'update-ww-distribution',
        DELETE_WWQUOTE = 'delete-ww-quote',

        UPDATE_WWQUOTE_NOTE = 'update-ww-quote-note',
        DELETE_WWQUOTE_NOTE = 'delete-ww-quote-note',

        UPDATE_OPPORTUNITY = 'update-opportunity',
        DELETE_OPPORTUNITY = 'delete-opportunity',

        UPDATE_WWASSET = 'update-ww-asset',
        DELETE_WWASSET = 'delete-ww-asset',

        UPDATE_SORDER = 'update-sales-order',
        DELETE_SORDER = 'delete-sales-order'
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

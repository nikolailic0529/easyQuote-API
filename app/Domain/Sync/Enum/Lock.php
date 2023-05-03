<?php

namespace App\Domain\Sync\Enum;

use App\Domain\Sync\Exceptions\InvalidEnumKeyException;
use App\Foundation\Support\Enum\Enum;

/**
 * \App\Domain\Sync\Enum\Lock.
 *
 * @method static CREATE_QUOTE(string $id)
 * @method static UPDATE_QUOTE(string $id)
 * @method static DELETE_QUOTE(string $id)
 * @method static UPDATE_QUOTE_FILE(string $id)
 * @method static CREATE_CONTRACT(string $id)
 * @method static UPDATE_CONTRACT(string $id)
 * @method static DELETE_CONTRACT(string $id)
 * @method static UPDATE_USER(string $id)
 * @method static DELETE_USER(string $id)
 * @method static UPDATE_WWQUOTE(string $id)
 * @method static DELETE_WWQUOTE(string $id)
 * @method static UPDATE_WWDISTRIBUTION(string $id)
 * @method static UPDATE_WWQUOTE_NOTE(string $id)
 * @method static DELETE_WWQUOTE_NOTE(string $id)
 * @method static UPDATE_COMPANY_NOTE(string $id)
 * @method static DELETE_COMPANY_NOTE(string $id)
 * @method static SYNC_OPPORTUNITY(string $id)
 * @method static UPDATE_OPPORTUNITY(string $id)
 * @method static DELETE_OPPORTUNITY(string $id)
 * @method static SYNC_COMPANY(string $id)
 * @method static CREATE_WWASSET_FOR_QUOTE(string $id)
 * @method static UPDATE_WWASSET(string $id)
 * @method static DELETE_WWASSET(string $id)
 * @method static UPDATE_SORDER(string $id)
 * @method static DELETE_SORDER(string $id)
 * @method static UPDATE_IMPORTABLE_COLUMN(string $id)
 * @method static DELETE_IMPORTABLE_COLUMN(string $id)
 */
final class Lock extends Enum
{
    const CREATE_QUOTE = 'create-quote';
    const UPDATE_QUOTE = 'update-quote';
    const DELETE_QUOTE = 'delete-quote';
    const UPDATE_QUOTE_FILE = 'update-quote-file';
    const CREATE_CONTRACT = 'create-quote-contract';
    const UPDATE_CONTRACT = 'update-quote-contract';
    const DELETE_CONTRACT = 'delete-quote-contract';
    const UPDATE_USER = 'update-user';
    const DELETE_USER = 'delete-user';
    const CREATE_WWQUOTE = 'create-ww-quote';
    const UPDATE_WWQUOTE = 'update-ww-quote';
    const UPDATE_WWDISTRIBUTION = 'update-ww-distribution';
    const DELETE_WWQUOTE = 'delete-ww-quote';
    const UPDATE_WWQUOTE_NOTE = 'update-ww-quote-note';
    const DELETE_WWQUOTE_NOTE = 'delete-ww-quote-note';
    const UPDATE_COMPANY_NOTE = 'update-company-note';
    const DELETE_COMPANY_NOTE = 'delete-company-note';
    const SYNC_OPPORTUNITY = 'sync-opportunity';
    const UPDATE_OPPORTUNITY = 'update-opportunity';
    const DELETE_OPPORTUNITY = 'delete-opportunity';
    const SYNC_COMPANY = 'sync-company';
    const CREATE_WWASSET_FOR_QUOTE = 'create-ww-asset-for-quote';
    const UPDATE_WWASSET = 'update-ww-asset';
    const DELETE_WWASSET = 'delete-ww-asset';
    const UPDATE_SORDER = 'update-sales-order';
    const DELETE_SORDER = 'delete-sales-order';
    const CREATE_IMPORTABLE_COLUMN = 'create-importable-column';
    const UPDATE_IMPORTABLE_COLUMN = 'update-importable-column';
    const DELETE_IMPORTABLE_COLUMN = 'delete-importable-column'
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

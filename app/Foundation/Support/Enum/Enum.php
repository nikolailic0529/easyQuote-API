<?php

namespace App\Foundation\Support\Enum;

abstract class Enum
{
    /**
     * Constants cache.
     *
     * @var array
     */
    protected static $constCacheArray = [];

    /**
     * Get all of the constants defined on the class.
     */
    protected static function getConstants(): array
    {
        $calledClass = get_called_class();

        if (!array_key_exists($calledClass, static::$constCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            static::$constCacheArray[$calledClass] = $reflect->getConstants();
        }

        return static::$constCacheArray[$calledClass];
    }

    /**
     * Get all of the enum keys.
     */
    public static function getKeys(): array
    {
        return array_keys(static::getConstants());
    }

    /**
     * Get the key for a single enum value.
     *
     * @param mixed $value
     */
    public static function getKey($value): string
    {
        return array_search($value, static::getConstants(), true);
    }

    /**
     * Get the value for a single enum key.
     *
     * @return mixed
     */
    public static function getValue(string $key)
    {
        return static::getConstants()[$key];
    }

    /**
     * Get all of the enum values.
     */
    public static function getValues(): array
    {
        return array_values(static::getConstants());
    }

    /**
     * Check that the enum contains a specific key.
     */
    public static function hasKey(string $key): bool
    {
        return in_array($key, static::getKeys(), true);
    }
}
